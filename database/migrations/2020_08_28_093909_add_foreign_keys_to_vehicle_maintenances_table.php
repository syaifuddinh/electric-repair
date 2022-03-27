<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToVehicleMaintenancesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vehicle_maintenances', function(Blueprint $table)
		{
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_id')->references('id')->on('vehicles')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vendor_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_id', 'vehicle_maintenances_wh_id_foreign')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vehicle_maintenances', function(Blueprint $table)
		{
			$table->dropForeign('vehicle_maintenances_company_id_foreign');
			$table->dropForeign('vehicle_maintenances_vehicle_id_foreign');
			$table->dropForeign('vehicle_maintenances_vendor_id_foreign');
			$table->dropForeign('vehicle_maintenances_wh_id_foreign');
		});
	}

}
