<?php
namespace Ashish\UserDiscounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
	protected $table = 'discounts';
	protected $fillable = [
		'code','title','percentage','fixed_amount','starts_at','ends_at','active','per_user_cap'
	];
	protected $casts = [
		'starts_at' => 'datetime',
		'ends_at' => 'datetime',
		'active' => 'boolean',
	];

	public function userDiscounts(): HasMany
	{
		return $this->hasMany(UserDiscount::class);
	}

	public function isActive(): bool
	{
		if (!$this->active) return false;
		$now = now();
		if ($this->starts_at && $now->lt($this->starts_at)) return false;
		if ($this->ends_at && $now->gt($this->ends_at)) return false;
		return true;
	}
}
