<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 */
	public function up(): void
	{
		Schema::create('uploads', function (Blueprint $table) {
			$table->id();
			$table->string('uuid')->index();
			$table->string('filename');
			$table->string('checksum')->nullable();
			$table->integer('total_chunks')->nullable();
			$table->integer('uploaded_chunks')->default(0);
			$table->bigInteger('size')->nullable();
			$table->string('mimetype')->nullable();
			$table->boolean('completed')->default(false);
			$table->string('entity_type')->nullable();
			$table->unsignedBigInteger('entity_id')->nullable();
			$table->timestamps();
			$table->unique(['uuid','filename']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('uploads');
	}
};
