<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessBulkProducts;
use App\Models\Product;

class CsvImportTest extends TestCase
{
	public function test_csv_import_upsert()
	{
		$csvPath = storage_path('app/mock_products_test.csv');

		if (!file_exists($csvPath)) {
			$file = fopen($csvPath, 'w');
			fputcsv($file, ['sku', 'name', 'description', 'price', 'images']);
			for ($i = 1; $i <= 10; $i++) {
				fputcsv($file, [
					"SKU{$i}",
					"Product {$i}",
					"This is the description for Product {$i}",
					rand(100, 5000),
					"Ashish.jpeg"
				]);
			}
			fclose($file);
		}

		$this->assertFileExists($csvPath);
		Queue::fake();
		ProcessBulkProducts::dispatch($csvPath);
		Queue::assertPushed(ProcessBulkProducts::class);
		$job = new ProcessBulkProducts($csvPath);
		$job->handle();
		$this->assertDatabaseHas('products', [
			'sku' => 'SKU1',
			'name' => 'Product 1',
		]);
		$this->assertDatabaseHas('products', [
			'sku' => 'SKU10',
		]);
		$this->assertEquals(10, Product::count());
	}
}
