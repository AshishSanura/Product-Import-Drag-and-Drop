<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\ProductImage;
class Product extends Model
{
	protected $fillable = [
		'sku',
		'name',
		'description',
		'price',
		'primary_image'
	];

	public function images()
	{
		return $this->hasMany(ProductImage::class);
	}
}
