<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDeliveryOrderDriversTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('delivery_order_drivers', function(Blueprint $table)
		{
			$table->foreign('cancelled_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('driver_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('from_address_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('from_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('manifest_id')->references('id')->on('manifests')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('to_address_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('to_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_id')->references('id')->on('vehicles')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vendor_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('delivery_order_drivers', function(Blueprint $table)
		{
			$table->dropForeign('delivery_order_drivers_cancelled_by_foreign');
			$table->dropForeign('delivery_order_drivers_create_by_foreign');
			$table->dropForeign('delivery_order_drivers_driver_id_foreign');
			$table->dropForeign('delivery_order_drivers_from_address_id_foreign');
			$table->dropForeign('delivery_order_drivers_from_id_foreign');
			$table->dropForeign('delivery_order_drivers_manifest_id_foreign');
			$table->dropForeign('delivery_order_drivers_to_address_id_foreign');
			$table->dropForeign('delivery_order_drivers_to_id_foreign');
			$table->dropForeign('delivery_order_drivers_vehicle_id_foreign');
			$table->dropForeign('delivery_order_drivers_vendor_id_foreign');
		});
	}

}
