<?php

namespace Ashish\UserDiscounts\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DiscountApplied
{
	use Dispatchable, SerializesModels;

	public $payload;
	public $userId;

	public function __construct($payload, int $userId)
	{
		$this->payload = $payload;
		$this->userId = $userId;
	}
}
