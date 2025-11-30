<?php
namespace Ashish\UserDiscounts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ashish\UserDiscounts\Models\Discount;

class DiscountAssigned
{
	use Dispatchable;
	public $discount;
	public $userId;

	public function __construct(Discount $discount, $userId)
	{
		$this->discount = $discount;
		$this->userId = $userId;
	}
}
