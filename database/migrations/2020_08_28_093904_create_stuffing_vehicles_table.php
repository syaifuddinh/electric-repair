<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStuffingVehiclesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stuffing_vehicles', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('stuffing_id');
			$table->string('space_type', 20);
			$table->string('no_seal', 20);
			$table->integer('carrier_size')->nullable();
			$table->string('driver_name', 150);
			$table->timestamps();
			$table->string('carrier_name', 100);
			$table->string('no_carrier', 100);
			$table->string('no_container', 100);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('stuffing_vehicles');
	}

}
