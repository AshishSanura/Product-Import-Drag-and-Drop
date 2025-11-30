<?php

namespace Ashish\UserDiscounts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ashish\UserDiscounts\Models\Discount;

class DiscountRevoked
{
	use Dispatchable, SerializesModels;

	public $discount;
	public $userId;

	public function __construct(Discount $discount, int $userId)
	{
		$this->discount = $discount;
		$this->userId = $userId;
	}
}
