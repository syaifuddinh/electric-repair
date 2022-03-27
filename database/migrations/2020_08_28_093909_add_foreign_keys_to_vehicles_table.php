<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToVehiclesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vehicles', function(Blueprint $table)
		{
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('delivery_id')->references('id')->on('delivery_order_drivers')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('supplier_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_owner_id')->references('id')->on('vehicle_owners')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_variant_id')->references('id')->on('vehicle_variants')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vehicles', function(Blueprint $table)
		{
			$table->dropForeign('vehicles_company_id_foreign');
			$table->dropForeign('vehicles_delivery_id_foreign');
			$table->dropForeign('vehicles_supplier_id_foreign');
			$table->dropForeign('vehicles_vehicle_owner_id_foreign');
			$table->dropForeign('vehicles_vehicle_variant_id_foreign');
		});
	}

}
