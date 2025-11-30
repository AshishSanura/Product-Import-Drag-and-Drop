<?php
namespace Ashish\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Model;

class UserDiscount extends Model
{
	protected $table = 'user_discounts';
	protected $fillable = ['discount_id','user_id','usage_count'];

	public function discount()
	{
		return $this->belongsTo(Discount::class);
	}
}
