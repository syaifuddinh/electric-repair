<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContainersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('containers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('container_type_id')->unsigned();
			$table->integer('vessel_id')->unsigned();
			$table->integer('company_id')->unsigned();
			$table->integer('commodity_id')->unsigned();
			$table->integer('voyage_schedule_id')->unsigned();
			$table->integer('vendor_id')->unsigned()->nullable();
			$table->integer('customer_id')->unsigned()->nullable();
			$table->integer('manifest_id')->unsigned()->nullable();
			$table->integer('pickup_id')->unsigned()->nullable();
			$table->integer('work_order_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->string('container_no', 191);
			$table->date('booking_date');
			$table->string('booking_number', 191);
			$table->float('rent_price', 10, 0)->default(0);
			$table->dateTime('departure')->nullable();
			$table->dateTime('arrival')->nullable();
			$table->dateTime('stripping')->nullable();
			$table->string('seal_no', 191)->nullable();
			$table->date('seal_date')->nullable();
			$table->float('total_item', 10, 0)->default(0);
			$table->float('total_tonase', 10, 0)->default(0);
			$table->float('total_volume', 10, 0)->default(0);
			$table->integer('total_job_order')->default(0);
			$table->boolean('is_fcl')->default(1);
			$table->string('commodity', 191)->nullable();
			$table->timestamps();
			$table->dateTime('stuffing')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('containers');
	}

}
