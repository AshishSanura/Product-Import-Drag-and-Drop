<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Upload;
use App\Jobs\ProcessImageVariants;
use App\Models\Product;
use Exception;
use DB;

class ImageUploadController extends Controller
{
	protected $tempDir;
	protected $finalDir;

	public function __construct()
	{
		$this->tempDir = storage_path('app/public/uploads/temp');
		$this->finalDir = storage_path('app/public/uploads');
		if (!is_dir($this->tempDir)) mkdir($this->tempDir, 0755, true);
		if (!is_dir($this->finalDir)) mkdir($this->finalDir, 0755, true);
	}

	public function index()
	{
		return view('import');
	}

	public function init(Request $request)
	{
		$data = $request->validate([
			'uuid'=>'required|string',
			'filename'=>'required|string',
			'totalChunks'=>'nullable|integer',
			'checksum'=>'nullable|string',
			'entity_id'=>'nullable|integer',
			'entity_type'=>'nullable|string',
		]);

		$upload = Upload::updateOrCreate(
			['uuid'=>$data['uuid'],'filename'=>$data['filename']],
			[
				'total_chunks'=>$data['totalChunks'] ?? null,
				'checksum'=>$data['checksum'] ?? null,
				'entity_id'=>$data['entity_id'] ?? null,
				'entity_type'=>$data['entity_type'] ?? null,
			]
		);

		return response()->json(['success'=>true,'upload'=>$upload]);
	}

	public function status(Request $request)
	{
		$uuid = $request->query('uuid');
		$filename = $request->query('filename');
		if (!$uuid || !$filename) return response()->json(['error'=>'Missing params'],400);

		$found = [];
		$pattern = $this->tempDir . DIRECTORY_SEPARATOR . "{$uuid}_{$filename}.part*";
		foreach (glob($pattern) as $f) {
			if (preg_match('/\.part(\d+)$/',$f,$m)) $found[] = intval($m[1]);
		}
		return response()->json(['exists'=>$found]);
	}

	public function uploadChunk(Request $request)
	{
		try {
			$chunk = $request->file('file');
			$uuid  = $request->input('dzuuid') ?? $request->input('uuid');
			$index = $request->input('dzchunkindex') ?? $request->input('chunkIndex');
			$name  = $request->input('filename') ?? $request->input('name');

			if (!$chunk || $uuid===null || $index===null || !$name) {
				return response()->json(['error'=>'Invalid chunk params'],400);
			}

			$chunkFilename = "{$uuid}_{$name}.part{$index}";
			$chunkPath = $this->tempDir . DIRECTORY_SEPARATOR . $chunkFilename;

			if (file_exists($chunkPath)) {
				return response()->json(['success'=>true,'message'=>'Chunk exists']);
			}

			$chunk->move($this->tempDir, $chunkFilename);

			DB::table('uploads')->updateOrInsert(
				['uuid'=>$uuid,'filename'=>$name],
				['uploaded_chunks' => DB::raw('COALESCE(uploaded_chunks,0) + 1')]
			);

			return response()->json(['success'=>true]);
		} catch (Exception $e) {
			return response()->json(['error'=>$e->getMessage()],500);
		}
	}

	public function complete(Request $request)
	{
		try {
			$request->validate([
				'uuid'=>'required|string',
				'filename'=>'required|string',
				'totalChunks'=>'required|integer',
				'checksum'=>'nullable|string',
				'entity_id'=>'nullable|integer',
				'entity_type'=>'nullable|string',
			]);

			$uuid = $request->input('uuid');
			$filename = $request->input('filename');
			$totalChunks = (int)$request->input('totalChunks');
			$clientChecksum = $request->input('checksum');
			$entityId = $request->input('entity_id');
			$entityType = $request->input('entity_type') ?? Product::class;

			$prefixed = "{$uuid}_{$filename}";
			$finalPath = $this->finalDir . DIRECTORY_SEPARATOR . $prefixed;

			$existing = Upload::where('uuid',$uuid)->where('filename',$filename)->first();
			if ($existing && $existing->completed && file_exists($finalPath)) {
				return response()->json(['success'=>true,'message'=>'Already completed']);
			}

			$out = @fopen($finalPath,'wb');
			if (!$out) throw new Exception("Unable to open final file for writing. Check permissions.");

			if (!flock($out, LOCK_EX)) {
				fclose($out);
				throw new Exception("Could not acquire merge lock.");
			}

			for ($i=0;$i<$totalChunks;$i++){
				$chunkPath = $this->tempDir . DIRECTORY_SEPARATOR . "{$prefixed}.part{$i}";
				if (!file_exists($chunkPath)) {
					flock($out, LOCK_UN);
					fclose($out);
					@unlink($finalPath);
					throw new Exception("Missing chunk {$i}");
				}
				$data = file_get_contents($chunkPath);
				if ($data === false) {
					flock($out, LOCK_UN);
					fclose($out);
					@unlink($finalPath);
					throw new Exception("Unable to read chunk {$i}");
				}
				fwrite($out, $data);
			}

			flock($out, LOCK_UN);
			fclose($out);

			for ($i=0;$i<$totalChunks;$i++){
				@unlink($this->tempDir . DIRECTORY_SEPARATOR . "{$prefixed}.part{$i}");
			}

			$computed = hash_file('sha256',$finalPath);
			if ($clientChecksum && !hash_equals($clientChecksum,$computed)) {
				@unlink($finalPath);
				throw new Exception("Checksum mismatch");
			}

			$upload = Upload::updateOrCreate(
				['uuid'=>$uuid,'filename'=>$filename],
				[
					'checksum'=>$computed,
					'size'=>filesize($finalPath),
					'mimetype'=>mime_content_type($finalPath),
					'completed'=>true,
					'entity_type'=>$entityType,
					'entity_id'=>$entityId,
					'total_chunks'=>$totalChunks,
					'uploaded_chunks'=>$totalChunks,
				]
			);

			ProcessImageVariants::dispatch($upload->id, $prefixed);

			return response()->json(['success'=>true,'message'=>'Image Uploaded successfully.','upload'=>$upload]);
		} catch (Exception $e) {
			return response()->json(['error'=>$e->getMessage()],500);
		}
	}
}
