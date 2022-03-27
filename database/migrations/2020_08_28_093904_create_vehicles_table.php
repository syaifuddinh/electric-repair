<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVehiclesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vehicles', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('vehicle_variant_id')->unsigned()->nullable();
			$table->integer('vehicle_owner_id')->unsigned();
			$table->integer('supplier_id')->unsigned();
			$table->string('code', 191)->nullable();
			$table->string('nopol', 191);
			$table->string('chassis_no', 191)->nullable();
			$table->string('machine_no', 191)->nullable();
			$table->string('color', 191)->nullable();
			$table->date('date_manufacture')->nullable();
			$table->date('date_operation')->nullable();
			$table->boolean('is_active');
			$table->string('not_active_reason', 191)->nullable();
			$table->string('stnk_no', 191)->nullable();
			$table->string('stnk_name', 191)->nullable();
			$table->date('stnk_date');
			$table->string('stnk_address', 191)->nullable();
			$table->string('bpkb_no', 191)->nullable();
			$table->date('kir_date');
			$table->integer('initial_km')->default(0);
			$table->date('initial_km_date')->nullable();
			$table->integer('last_km')->default(0);
			$table->date('last_km_date')->nullable();
			$table->integer('daily_distance')->default(0);
			$table->string('gps_no', 191)->nullable();
			$table->integer('serep_tire')->default(0);
			$table->integer('is_trailer')->default(1);
			$table->integer('trailer_size')->nullable();
			$table->integer('max_tonase')->nullable();
			$table->integer('max_volume')->nullable();
			$table->timestamps();
			$table->integer('delivery_id')->unsigned()->nullable();
			$table->integer('is_internal')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('vehicles');
	}

}
