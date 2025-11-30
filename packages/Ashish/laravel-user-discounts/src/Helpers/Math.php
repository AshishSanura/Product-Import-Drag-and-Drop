<?php
namespace Ashish\UserDiscounts\Helpers;

class Math
{
	public static function applyRounding(float $value, string $mode, int $decimals): float
	{
		$factor = pow(10, $decimals);
		switch ($mode) {
			case 'ceil':
				return ceil($value * $factor) / $factor;
			case 'floor':
				return floor($value * $factor) / $factor;
			default:
				return round($value, $decimals);
		}
	}
}
