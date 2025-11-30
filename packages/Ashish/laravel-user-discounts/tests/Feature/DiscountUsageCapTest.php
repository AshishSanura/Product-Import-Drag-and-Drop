<?php
namespace Ashish\UserDiscounts\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Ashish\UserDiscounts\UserDiscountsServiceProvider;
use Ashish\UserDiscounts\Models\Discount;
use Illuminate\Support\Facades\DB;

class DiscountUsageCapTest extends TestCase
{
	protected function getPackageProviders($app)
	{
		return [UserDiscountsServiceProvider::class];
	}

	protected function getEnvironmentSetUp($app)
	{
		$app['config']->set('database.default', 'mysql');
		$app['config']->set('database.connections.mysql', [
			'driver' => 'mysql',
			'host' => env('DB_HOST', '127.0.0.1'),
			'database' => env('DB_DATABASE', 'laravel_product_import_discounts'),
			'username' => env('DB_USERNAME', 'root'),
			'password' => env('DB_PASSWORD', ''),
			'charset'   => 'utf8mb4',
			'collation' => 'utf8mb4_unicode_ci',
			'prefix'    => '',
			'strict'    => true,
			'engine'    => null,
		]);
	}

	protected function setUp(): void
	{
		parent::setUp();
		if (!\Schema::hasTable('discounts')) {
			$this->loadMigrationsFrom(__DIR__.'/../../src/database/migrations');
		}

		DB::table('user_discounts')->where('user_id', 123)->delete();
	}

	public function test_usage_cap_prevents_overuse()
	{
		$d = Discount::firstOrCreate([
			'code' => 'ONEUSE'
		], [
			'percentage'=>50,
			'active'=>true,
			'per_user_cap'=>1
		]);

		$manager = $this->app->make('discounts');

		$result1 = $manager->apply(100.00, 123, [$d]);
		$this->assertEquals(50.00, round(100 - $result1['final'], 2), "पहली apply में 50% discount लगना चाहिए");

		$result2 = $manager->apply(100.00, 123, [$d]);
		$this->assertEquals(0, count($result2['applied']), "दूसरी apply में discount नहीं लगना चाहिए (cap reached)");
	}
}
