<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountsTable extends Migration
{
	public function up()
	{
		Schema::create('discounts', function (Blueprint $table) {
			$table->id();
			$table->string('code')->unique();
			$table->string('title')->nullable();
			$table->decimal('percentage', 5, 2)->nullable(); 
			$table->decimal('fixed_amount', 12, 2)->nullable();
			$table->dateTime('starts_at')->nullable();
			$table->dateTime('ends_at')->nullable();
			$table->boolean('active')->default(true);
			$table->unsignedInteger('per_user_cap')->nullable();
			$table->timestamps();
		});
	}

	public function down()
	{
		Schema::dropIfExists('discounts');
	}
}
