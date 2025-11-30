@extends('layouts.master-layout')

@section('content')
	<div class="container">
		<div class="card mt-5">
			<h2 class="card-header">Bulk CSV Import + Chunked Drag-and-Drop Image Upload</h2>
			<div class="card-body">
				<div class="row">
					<!-- CSV Import Card -->
					<div class="col-md-6">
						<div class="card shadow-sm mb-4">
							<div class="card-header bg-secondary text-white">
								<h5 class="mb-0">Import CSV File</h5>
							</div>
							<div class="card-body equal-card-body">
								<form id="csvForm" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-center">
									@csrf
									<input type="file" name="csv" accept=".csv" class="form-control" required>
									<button type="submit" class="btn btn-success">Import CSV</button>
								</form>
								<div id="result" class="mt-2"></div>
							</div>
						</div>
					</div>
					<!-- Drag-and-Drop Image Upload Card -->
					<div class="col-md-6">
						<div class="card shadow-sm mb-4">
							<div class="card-header bg-secondary text-white">
								<h5 class="mb-0">Drag-and-Drop Image Upload</h5>
							</div>
							<div class="card-body equal-card-body">
								<form action="{{ route('upload.chunk') }}" class="dropzone" id="imgUpload">@csrf</form>
								@csrf
								<div class="mt-3">
									<button id="completeBtn" class="btn btn-success">Image Upload</button>
									<div id="status" class="mt-2"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('scripts')
	<script type="text/javascript">

		// ================= CSV Import =================
		$(document).ready(function(){		
			$('#csvForm').on('submit', function(e){
				e.preventDefault();
				var formData = new FormData(this);
				$('#result').html('');
				$.ajax({
					url:'{{ route("products.import") }}',
					type:'POST',
					data:formData,
					processData:false,
					contentType:false,
					success:function(data)
					{
						$('#result').html('<div class="alert alert-success">'+data.message+'</div>');

						const interval = setInterval(function(){
							$.get('{{ url("products/import-summary") }}', function(summary){
								if(summary.completed || summary.error){
									clearInterval(interval);

									let html = `<div class="card shadow-sm">
										<div class="card-header bg-info text-white">
											<h5 class="mb-0">CSV Import Summary</h5>
										</div>
										<div class="card-body">`;

									if(summary.error){
										html += `<div class="alert alert-danger">${summary.error}</div>`;
									} else {
										html += `<ul class="list-group list-group-flush">
											<li class="list-group-item">Total: ${summary.total}</li>
											<li class="list-group-item">Imported: ${summary.imported}</li>
											<li class="list-group-item">Updated: ${summary.updated}</li>
											<li class="list-group-item">Invalid: ${summary.invalid}</li>
											<li class="list-group-item">Duplicates: ${summary.duplicates}</li>
										</ul>`;
									}

									html += `</div></div>`;
									$('#result').html(html);
								}
							});
						}, 2000);
					},
					error:function(err){
						$('#result').html('<div class="alert alert-danger">Something went wrong. Please try again.</div>');
					}
				});
			});

		});

		// ================= Chunked Image Upload =================
		Dropzone.autoDiscover = false;

		function arrayBufferToHex(buffer) {
			const bytes = new Uint8Array(buffer);
			return Array.from(bytes).map(b => b.toString(16).padStart(2,'0')).join('');
		}

		async function computeSHA256(file) {
			const buf = await file.arrayBuffer();
			const hashBuffer = await crypto.subtle.digest('SHA-256', buf);
			return arrayBufferToHex(hashBuffer);
		}

		const dz = new Dropzone("#imgUpload", {
			url: "{{ route('upload.chunk') }}",
			paramName: "file",
			chunking: true,
			forceChunking: true,
			chunkSize: 1024 * 512,
			parallelChunkUploads: false,
			retryChunks: true,
			retryChunksLimit: 3,
			addRemoveLinks: true,
			acceptedFiles: "image/*",
			init: function() {
				this.on("sending", function(file, xhr, formData) {
					formData.append("_token", document.querySelector('meta[name=csrf-token]').getAttribute('content'));
					formData.append("dzuuid", file.upload.uuid);
					formData.append("dzchunkindex", file.upload.currentChunk ?? 0);
					
					formData.append("filename", file.upload.filename);
				});
				
				this.on("uploadprogress", function(file, progress) {
					$("#status").html('<div class="alert alert-info">Upload progress: '+Math.round(progress)+'%</div>');
				});
				this.on("success", function(file,res) {
					$("#status").html('<div class="alert alert-success">Chunk uploaded</div>');
				});
				this.on("error", function(file, message, xhr) {
					let msg = message;
					if (xhr && xhr.response) {
						try { msg = JSON.parse(xhr.response).error || msg; } catch(e){}
					}
					$("#status").html('<div class="alert alert-danger">'+msg+'</div>');
				});
			}
		});

		document.getElementById('completeBtn').addEventListener('click', async function() {
			const file = dz.files[0];
			if (!file) return alert('Select a file first');
			$("#status").html('<div class="alert alert-info">Computing checksum...</div>');
			const checksum = await computeSHA256(file);
			$("#status").html('<div class="alert alert-info">Checksum ready</div>');
			const uuid = file.upload.uuid;
			const filename = file.upload.filename;
			const totalChunks = file.upload.totalChunkCount || Math.ceil(file.size / (1024*512));
			
			await fetch("{{ route('upload.init') }}", {
				method:'POST',
				headers:{'Content-Type':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').getAttribute('content')},
				body: JSON.stringify({uuid, filename, totalChunks, checksum, entity_id:1})
			});
			$("#status").html('<div class="alert alert-info">Finalizing merge...</div>');
			const body = new URLSearchParams();
			body.append('_token', document.querySelector('meta[name=csrf-token]').getAttribute('content'));
			body.append('uuid', uuid);
			body.append('filename', filename);
			body.append('totalChunks', totalChunks);
			body.append('checksum', checksum);
			body.append('entity_id', 1);
			const res = await fetch("{{ route('upload.complete') }}", { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: body.toString() });
			const json = await res.json();
			
			if (res.ok) {
				$("#status").html('<div class="alert alert-success">'+json.message+'</div>');
				setTimeout(function(){
					location.reload();
				}, 5000);
			} 
			else {
				$("#status").html('<div class="alert alert-danger">'+(json.error||'complete failed')+'</div>');
			}
		});

	</script>
@endsection