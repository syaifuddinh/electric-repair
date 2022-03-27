<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobStatusHistoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_status_histories', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('delivery_id')->unsigned();
			$table->integer('job_status_id')->unsigned();
			$table->timestamps();
			$table->integer('vendor_id')->unsigned()->nullable();
			$table->integer('driver_id')->unsigned()->nullable();
			$table->integer('vehicle_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_status_histories');
	}

}
