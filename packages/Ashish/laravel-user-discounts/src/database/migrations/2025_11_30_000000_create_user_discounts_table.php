<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserDiscountsTable extends Migration
{
	public function up()
	{
		Schema::create('user_discounts', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('discount_id');
			$table->unsignedBigInteger('user_id');
			$table->unsignedInteger('usage_count')->default(0);
			$table->timestamps();

			$table->unique(['discount_id','user_id']);
			$table->foreign('discount_id')->references('id')->on('discounts')->onDelete('cascade');
		});
	}

	public function down()
	{
		Schema::dropIfExists('user_discounts');
	}
}
