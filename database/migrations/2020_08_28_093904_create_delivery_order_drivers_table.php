<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDeliveryOrderDriversTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('delivery_order_drivers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('manifest_id')->unsigned();
			$table->integer('vehicle_id')->unsigned()->nullable();
			$table->integer('driver_id')->unsigned()->nullable();
			$table->string('driver_name', 100)->nullable();
			$table->string('nopol', 100)->nullable();
			$table->integer('from_id')->unsigned()->nullable();
			$table->integer('from_address_id')->unsigned()->nullable();
			$table->integer('to_id')->unsigned()->nullable();
			$table->integer('to_address_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->string('commodity_name', 191);
			$table->integer('status')->default(1);
			$table->string('latitude_start', 191)->nullable();
			$table->string('longitude_start', 191)->nullable();
			$table->string('latitude_end', 191)->nullable();
			$table->string('longitude_end', 191)->nullable();
			$table->timestamp('pick_date')->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->dateTime('finish_date')->default('0000-00-00 00:00:00');
			$table->timestamps();
			$table->integer('vendor_id')->unsigned()->nullable();
			$table->boolean('is_internal')->default(1);
			$table->boolean('is_finish')->default(0);
			$table->integer('cancelled_by')->unsigned()->nullable();
			$table->string('cancel_reason', 256)->nullable();
			$table->integer('job_status_id')->unsigned()->default(0)->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('delivery_order_drivers');
	}

}
