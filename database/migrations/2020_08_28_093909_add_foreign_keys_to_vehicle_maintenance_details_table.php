<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToVehicleMaintenanceDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vehicle_maintenance_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('vehicle_maintenances')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_maintenance_type_id')->references('id')->on('vehicle_maintenance_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vehicle_maintenance_details', function(Blueprint $table)
		{
			$table->dropForeign('vehicle_maintenance_details_header_id_foreign');
			$table->dropForeign('vehicle_maintenance_details_item_id_foreign');
			$table->dropForeign('vehicle_maintenance_details_vehicle_maintenance_type_id_foreign');
		});
	}

}
