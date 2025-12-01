<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ImageUploadController;


// CSV Import
Route::get('/',[ProductImportController::class,'index']);
Route::get('products/import',[ProductImportController::class,'index']);
Route::post('products/import',[ProductImportController::class,'import'])->name('products.import');

Route::get('products/import-summary', function(){
	$summary = Cache::get('csv_import_summary', [
		'total'=>0,'imported'=>0,'updated'=>0,'invalid'=>0,'duplicates'=>0,'completed'=>false
	]);
	return response()->json($summary);
});

// Chunked Image Upload
Route::post('upload/init', [ImageUploadController::class, 'init'])->name('upload.init');
Route::get('upload/status', [ImageUploadController::class, 'status'])->name('upload.status');
Route::post('upload/chunk', [ImageUploadController::class, 'uploadChunk'])->name('upload.chunk');
Route::post('upload/complete', [ImageUploadController::class, 'complete'])->name('upload.complete');