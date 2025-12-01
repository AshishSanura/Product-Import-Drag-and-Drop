<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('make:mock-csv', function () {
	$file = fopen(storage_path('app/mock_large_products.csv'), 'w');

	fputcsv($file, ['sku', 'name', 'description', 'price', 'images']);

	for ($i = 1; $i <= 10000; $i++) {
		fputcsv($file, [
			"SKU{$i}",
			"Product {$i}",
			"This is the description for Product {$i}",
			rand(100, 5000),
		]);
	}

	fclose($file);
	$this->info("Mock CSV with descriptions created!");
});