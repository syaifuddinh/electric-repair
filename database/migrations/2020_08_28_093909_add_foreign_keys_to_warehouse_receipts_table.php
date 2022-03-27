<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToWarehouseReceiptsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('warehouse_receipts', function(Blueprint $table)
		{
			$table->foreign('collectible_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('job_order_pengiriman_id')->references('id')->on('job_orders')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('receiver_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('type_transaction_id')->references('id')->on('type_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_staff_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('NO ACTION');
			$table->foreign('work_order_id')->references('id')->on('work_orders')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('warehouse_receipts', function(Blueprint $table)
		{
			$table->dropForeign('warehouse_receipts_collectible_id_foreign');
			$table->dropForeign('warehouse_receipts_create_by_foreign');
			$table->dropForeign('warehouse_receipts_customer_id_foreign');
			$table->dropForeign('warehouse_receipts_job_order_pengiriman_id_foreign');
			$table->dropForeign('warehouse_receipts_receiver_id_foreign');
			$table->dropForeign('warehouse_receipts_type_transaction_id_foreign');
			$table->dropForeign('warehouse_receipts_vehicle_type_id_foreign');
			$table->dropForeign('warehouse_receipts_warehouse_id_foreign');
			$table->dropForeign('warehouse_receipts_warehouse_staff_id_foreign');
			$table->dropForeign('warehouse_receipts_work_order_id_foreign');
		});
	}

}
