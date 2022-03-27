<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStuffingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stuffings', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_id');
			$table->integer('job_order_id');
			$table->integer('vehicle_id');
			$table->integer('container_id');
			$table->time('start_time');
			$table->time('end_time');
			$table->string('staff_gudang_name', 200);
			$table->timestamps();
			$table->integer('is_overtime')->default(0);
			$table->text('description', 65535)->nullable();
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
		Schema::drop('stuffings');
	}

}
