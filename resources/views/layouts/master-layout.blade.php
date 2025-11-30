<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Bulk CSV Import & Drag-and-Drop Image</title>
	
	<!-- Include Style -->
	@include('layouts.style')
</head>
<body class="bg-light">
	<!-- Start Content -->
	@yield('content') 
	<!-- End Content -->
	
	<!-- Include Script -->
	@include('layouts.script')

 	@yield('scripts')
</body>
</html>