<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountAuditsTable extends Migration
{
	public function up()
	{
		Schema::create('discount_audits', function (Blueprint $table) {
			$table->id();
			$table->unsignedBigInteger('discount_id')->nullable();
			$table->unsignedBigInteger('user_id')->nullable();
			$table->string('action');
			$table->json('meta')->nullable();
			$table->timestamps();
			$table->index('discount_id');
		});
	}

	public function down()
	{
		Schema::dropIfExists('discount_audits');
	}
}
