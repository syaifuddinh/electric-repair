<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVehicleMaintenanceDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vehicle_maintenance_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('vehicle_maintenance_type_id')->unsigned();
			$table->integer('item_id')->unsigned();
			$table->integer('tipe_kegiatan');
			$table->float('qty_rencana', 10, 0)->default(0);
			$table->float('cost_rencana', 10, 0)->default(0);
			$table->float('total_rencana', 10, 0)->default(0);
			$table->float('qty_realisasi', 10, 0)->default(0);
			$table->float('cost_realisasi', 10, 0)->default(0);
			$table->float('total_realisasi', 10, 0)->default(0);
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
		Schema::drop('vehicle_maintenance_details');
	}

}
