<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVehicleMaintenanceTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vehicle_maintenance_types', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name', 191);
			$table->integer('type');
			$table->integer('interval');
			$table->float('cost', 10, 0);
			$table->boolean('is_repeat');
			$table->string('description', 191)->nullable();
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('vehicle_maintenance_types');
	}

}
