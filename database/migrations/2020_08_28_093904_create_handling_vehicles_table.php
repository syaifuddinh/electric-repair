<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHandlingVehiclesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('handling_vehicles', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('handling_id');
			$table->string('space_type', 30);
			$table->string('no_seal', 100);
			$table->timestamps();
			$table->string('type', 10)->nullable();
			$table->string('carrier_name', 100);
			$table->integer('carrier_size')->default(0);
			$table->string('driver_name', 200);
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
		Schema::drop('handling_vehicles');
	}

}
