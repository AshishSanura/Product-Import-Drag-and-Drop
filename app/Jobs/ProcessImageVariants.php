<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Models\ProductImage;
use App\Models\Product;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class ProcessImageVariants implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	public $uploadId;
	public $prefixedFilename;

	public function __construct($uploadId, $prefixedFilename)
	{
		$this->uploadId = $uploadId;
		$this->prefixedFilename = $prefixedFilename;
	}

	public function handle()
	{
		$upload = Upload::find($this->uploadId);
		if (!$upload) return;

		$filesDir = storage_path('app/public/uploads');
		$finalPath = $filesDir . DIRECTORY_SEPARATOR . $this->prefixedFilename;
		if (!file_exists($finalPath)) {
			throw new Exception("Final file missing for variants: {$finalPath}");
		}

		$sizes = [256,512,1024];
		$manager = new ImageManager(new Driver());

		foreach ($sizes as $size) {
			$variantPath = $filesDir . DIRECTORY_SEPARATOR . "{$size}_{$this->prefixedFilename}";
			$img = $manager->read($finalPath);
			if (method_exists($img, 'scaleDown')) {
				$img->scaleDown($size)->save($variantPath);
			} else {
				$img->resize(null, $size, function ($constraint) {
					$constraint->aspectRatio();
					$constraint->upsize();
				})->save($variantPath);
			}
		}

		if ($upload->entity_id && $upload->entity_type === Product::class) {
			$productId = $upload->entity_id;
			$product = Product::find($productId);

			if ($product) {
				ProductImage::updateOrCreate(
					['product_id' => $productId, 'filename' => $this->prefixedFilename, 'variant' => 'original'],
					[]
				);

				foreach ($sizes as $s) {
					ProductImage::updateOrCreate(
						['product_id' => $productId, 'filename' => $this->prefixedFilename, 'variant' => (string)$s],
						[]
					);
				}

				if ($product->primary_image !== $this->prefixedFilename) {
					$product->primary_image = $this->prefixedFilename;
					$product->save();
				}
			} else {
				echo "Upload ID {$upload->id} references missing Product ID {$productId}\n";
			}
		}

	}
}
