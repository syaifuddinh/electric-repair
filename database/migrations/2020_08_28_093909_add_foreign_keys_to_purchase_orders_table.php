<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPurchaseOrdersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('purchase_orders', function(Blueprint $table)
		{
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('po_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('supplier_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_maintenance_id', 'purchase_orders_vm_id_foreign')->references('id')->on('vehicle_maintenances')->onUpdate('CASCADE')->onDelete('CASCADE');
			$table->foreign('warehouse_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('purchase_orders', function(Blueprint $table)
		{
			$table->dropForeign('purchase_orders_company_id_foreign');
			$table->dropForeign('purchase_orders_po_by_foreign');
			$table->dropForeign('purchase_orders_purchase_request_id_foreign');
			$table->dropForeign('purchase_orders_supplier_id_foreign');
			$table->dropForeign('purchase_orders_vm_id_foreign');
			$table->dropForeign('purchase_orders_warehouse_id_foreign');
		});
	}

}
