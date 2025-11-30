<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Product;
class ProductImage extends Model
{
	protected $fillable = [
		'product_id',
		'filename',
		'variant'
	];

	public function product()
	{
		return $this->belongsTo(Product::class);
	}
}
