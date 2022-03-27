<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPurchaseRequestDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('purchase_request_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('purchase_requests')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_id')->references('id')->on('vehicles')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_maintenance_detail_id', 'purchase_request_details_vmd_id_foreign')->references('id')->on('vehicle_maintenance_details')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('purchase_request_details', function(Blueprint $table)
		{
			$table->dropForeign('purchase_request_details_header_id_foreign');
			$table->dropForeign('purchase_request_details_item_id_foreign');
			$table->dropForeign('purchase_request_details_vehicle_id_foreign');
			$table->dropForeign('purchase_request_details_vmd_id_foreign');
		});
	}

}
