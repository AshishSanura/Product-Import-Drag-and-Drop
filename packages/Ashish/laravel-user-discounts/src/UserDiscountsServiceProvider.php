<?php
namespace Ashish\UserDiscounts;

use Illuminate\Support\ServiceProvider;
use Ashish\UserDiscounts\Services\DiscountManager;

class UserDiscountsServiceProvider extends ServiceProvider
{
	public function register()
	{
		if (!function_exists('config_path')) {
			function config_path($path = '') {
				return base_path($path);
			}
		}

		$this->mergeConfigFrom(
			__DIR__ . '/config/discounts.php',
			'discounts'
		);

		$this->app->singleton('discounts', function ($app) {
			return new DiscountManager($app['config']['discounts']);
		});
	}

	public function boot()
	{
		$this->publishes([
			__DIR__ . '/config/discounts.php' => config_path('discounts.php'),
		], 'config');

		$this->loadMigrationsFrom(
			__DIR__ . '/database/migrations'
		);
	}
}
