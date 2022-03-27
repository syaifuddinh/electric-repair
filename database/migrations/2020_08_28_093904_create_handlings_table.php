<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHandlingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('handlings', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_id');
			$table->integer('job_order_id');
			$table->integer('is_overtime')->default(0);
			$table->time('start_time')->default('00:00:00');
			$table->time('end_time')->default('00:00:00');
			$table->timestamps();
			$table->text('description', 65535);
			$table->string('staff_gudang_name', 200);
			$table->integer('status')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('handlings');
	}

}
