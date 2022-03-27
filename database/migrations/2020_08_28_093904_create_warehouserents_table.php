<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWarehouserentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('warehouserents', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_id');
			$table->integer('job_order_id');
			$table->text('description', 65535);
			$table->timestamps();
			$table->string('staff_gudang_name', 200)->nullable();
			$table->time('start_time')->nullable();
			$table->time('end_time')->nullable();
			$table->integer('status')->default(0);
			$table->integer('is_over_storage_cost')->default(0);
			$table->date('start_date')->nullable()->index();
			$table->date('end_date')->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('warehouserents');
	}

}
