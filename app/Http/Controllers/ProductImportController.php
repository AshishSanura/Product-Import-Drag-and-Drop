<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Jobs\ProcessBulkProducts;
use Exception;

class ProductImportController extends Controller
{
	public function index()
	{
		return view('import');
	}

	public function import(Request $request)
	{
		try {
			if(!$request->hasFile('csv')) {
				return response()->json(['error'=>'CSV file missing'],400);
			}

			$file = $request->file('csv');
			$path = $file->store('csv_imports', 'public');

			ProcessBulkProducts::dispatch($path);

			return response()->json([
				'message'=>'CSV uploaded successfully. Processing in background.'
			]);

		} catch (Exception $e) {
			return response()->json(['error'=>$e->getMessage()]);
		}
	}
}
