<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Cache;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

class ProcessBulkProducts implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
	protected $filePath;
	protected $batchSize = 500;

	public function __construct($filePath)
	{
		$this->filePath = $filePath;
	}

	public function handle(): void
	{
		$summary = ['total'=>0,'imported'=>0,'updated'=>0,'invalid'=>0,'duplicates'=>0];

		try {
			$fullPath = storage_path('app/public/'.$this->filePath);
			if(!file_exists($fullPath)) return;
			$handle = fopen($fullPath,'r');
			$header = fgetcsv($handle);
			$sku_list = [];
			$batch = [];

			while(($row = fgetcsv($handle)) !== false){
				$batch[] = $row;
				if(count($batch) >= $this->batchSize){
					$this->processBatch($batch, $summary, $header, $sku_list);
					$batch = [];
				}
			}

			if(count($batch) > 0){
				$this->processBatch($batch, $summary, $header, $sku_list);
			}

			fclose($handle);
			$summary['completed'] = true;
		} catch(Exception $e){
			$summary['error'] = "CSV Import Failed: ".$e->getMessage();
		}
		Cache::put('csv_import_summary', $summary, 3600);
	}

	private function processBatch(array $batch, array &$summary, array $header, array &$sku_list)
	{
		$manager = new ImageManager(new Driver());

		foreach($batch as $row)
		{
			$summary['total']++;
			$data = array_combine($header, $row);
			$sku  = $data['sku'] ?? null;
			$name = $data['name'] ?? null;

			if(!$name){
				$summary['invalid']++;
				continue;
			}

			if(in_array($name, $sku_list)){
				$summary['duplicates']++;
				continue;
			}

			$sku_list[] = $name;
			$product = Product::updateOrCreate(
				['sku'=>$sku],
				[
					'name'=>$name,
					'description'=>$data['description'] ?? null,
					'price'=>$data['price'] ?? null
				]
			);

			if($product->wasRecentlyCreated)
				$summary['imported']++;
			else
				$summary['updated']++;

			// Image variants
			if(isset($data['images']) && !empty($data['images'])){
				$images = explode(',', $data['images']);
				foreach($images as $img){
					$img = trim($img);
					$path = storage_path('app/public/products/'.$img);

					if(file_exists($path)){
						// Save original image
						ProductImage::updateOrCreate(
							['product_id'=>$product->id,'filename'=>$img,'variant'=>'original'],
							['variant'=>'original']
						);
						// Resize variants
						foreach([256,512,1024] as $size)
						{
							$variantPath = storage_path("app/public/products/{$size}_".$img);
							if(!file_exists($variantPath)){
								$image = $manager->read($path);
								$image->cover($size, $size)->save($variantPath);
							}
							ProductImage::updateOrCreate(
								['product_id'=>$product->id,'filename'=>$img,'variant'=>$size],
								['variant'=>$size]
							);
						}
					}
				}
				$product->update(['primary_image'=>trim($images[0])]);
			}
		}
		Cache::put('csv_import_summary', $summary, 3600);
	}
}