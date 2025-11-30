<?php
namespace Ashish\UserDiscounts\Services;

use Illuminate\Support\Facades\DB;
use Ashish\UserDiscounts\Models\Discount;
use Ashish\UserDiscounts\Models\UserDiscount;
use Ashish\UserDiscounts\Models\DiscountAudit;
use Ashish\UserDiscounts\Events\DiscountAssigned;
use Ashish\UserDiscounts\Events\DiscountRevoked;
use Ashish\UserDiscounts\Events\DiscountApplied;
use Ashish\UserDiscounts\Helpers\Math;

class DiscountManager
{
	protected $config;
	public function __construct(array $config = [])
	{
		$this->config = $config;
	}

	public function assign(Discount $discount, int $userId): UserDiscount
	{
		$ud = UserDiscount::firstOrCreate(
			['discount_id' => $discount->id, 'user_id' => $userId],
			['usage_count' => 0]
		);

		DiscountAudit::create(['discount_id'=>$discount->id,'user_id'=>$userId,'action'=>'assigned','meta'=>null]);
		event(new DiscountAssigned($discount, $userId));
		return $ud;
	}

	public function revoke(Discount $discount, int $userId): void
	{
		UserDiscount::where('discount_id',$discount->id)->where('user_id',$userId)->delete();
		DiscountAudit::create(['discount_id'=>$discount->id,'user_id'=>$userId,'action'=>'revoked','meta'=>null]);
		event(new DiscountRevoked($discount, $userId));
	}

	public function eligibleFor(Discount $discount, int $userId): bool
	{
		if (!$discount->isActive()) return false;

		if ($discount->per_user_cap) {
			$ud = UserDiscount::where('discount_id',$discount->id)->where('user_id',$userId)->first();
			$used = $ud ? $ud->usage_count : 0;
			if ($used >= $discount->per_user_cap) return false;
		}

		return true;
	}

	public function apply(float $amount, int $userId, array $discounts): array
	{
		$eligible = [];
		foreach ($discounts as $d) {
			if ($d instanceof Discount && $this->eligibleFor($d, $userId)) {
				$eligible[] = $d;
			}
		}

		if ($this->config['stacking_order'] ?? null === 'lowest_first') {
			usort($eligible, fn($a,$b)=>($a->percentage <=> $b->percentage));
		} else {
			usort($eligible, fn($a,$b)=>($b->percentage <=> $a->percentage));
		}

		$original = $amount;
		$running = $amount;
		$applied = [];
		$totalPercentageApplied = 0;

		foreach ($eligible as $d) {
			
			$discountValue = 0.0;
			if ($d->percentage) {
				$discountValue = ($running * floatval($d->percentage)) / 100.0;
			} elseif ($d->fixed_amount) {
				$discountValue = floatval($d->fixed_amount);
			}

			if (!empty($this->config['max_total_percentage']) && $d->percentage) {
				$totalPercentageApplied += floatval($d->percentage);
				if ($totalPercentageApplied > $this->config['max_total_percentage']) {
					$excess = $totalPercentageApplied - $this->config['max_total_percentage'];
					$effectivePercent = floatval($d->percentage) - $excess;
					if ($effectivePercent <= 0) {
						$totalPercentageApplied -= floatval($d->percentage);
						continue;
					}
					$discountValue = ($running * $effectivePercent) / 100.0;
					$appliedPercent = $effectivePercent;
				} else {
					$appliedPercent = floatval($d->percentage);
				}
			} else {
				$appliedPercent = $d->percentage ? floatval($d->percentage) : null;
			}

			$roundMode = $this->config['round_mode'] ?? 'round';
			$roundDecimals = $this->config['round_decimals'] ?? 2;
			$discountValue = Math::applyRounding((float)$discountValue, $roundMode, $roundDecimals);

			if ($discountValue <= 0) continue;

			$appliedSuccess = false;
			DB::transaction(function() use ($d, $userId, &$appliedSuccess) {
				if ($d->per_user_cap) {
					$updated = DB::table('user_discounts')
						->where('discount_id', $d->id)
						->where('user_id', $userId)
						->where('usage_count', '<', $d->per_user_cap)
						->increment('usage_count');
					if ($updated) {
						$appliedSuccess = true;
					} else {
						$exists = DB::table('user_discounts')
							->where('discount_id', $d->id)
							->where('user_id', $userId)
							->exists();
						if (!$exists) {
							DB::table('user_discounts')->insert([
								'discount_id'=>$d->id,'user_id'=>$userId,'usage_count'=>1,'created_at'=>now(),'updated_at'=>now()
							]);
							$appliedSuccess = true;
						} else {
							$appliedSuccess = false;
						}
					}
				} else {
					$exists = DB::table('user_discounts')
						->where('discount_id', $d->id)
						->where('user_id', $userId)
						->exists();
					if (!$exists) {
						DB::table('user_discounts')->insert([
							'discount_id'=>$d->id,'user_id'=>$userId,'usage_count'=>1,'created_at'=>now(),'updated_at'=>now()
						]);
					} else {
						DB::table('user_discounts')->where('discount_id',$d->id)->where('user_id',$userId)->increment('usage_count');
					}
					$appliedSuccess = true;
				}

				if ($appliedSuccess) {
					DiscountAudit::create([
						'discount_id'=>$d->id,
						'user_id'=>$userId,
						'action'=>'applied',
						'meta'=>json_encode(['amount_snapshot'=> (string) now()])
					]);
				}
			});

			if (!$appliedSuccess) {
				continue;
			}

			$running = max(0, $running - $discountValue);

			$applied[] = [
				'discount_id'=>$d->id,
				'code'=>$d->code,
				'amount'=>$discountValue,
				'applied_percent'=>$appliedPercent,
			];
		}

		$final = Math::applyRounding($running, $this->config['round_mode'] ?? 'round', $this->config['round_decimals'] ?? 2);

		event(new DiscountApplied(null, $userId));
		return [
			'original' => $original,
			'final' => $final,
			'applied' => $applied
		];
	}
}
