<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPurchaseRequestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('purchase_requests', function(Blueprint $table)
		{
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('reject_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('supplier_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_maintenance_id', 'purchase_requests_vm_id_foreign')->references('id')->on('vehicle_maintenances')->onUpdate('CASCADE')->onDelete('CASCADE');
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
		Schema::table('purchase_requests', function(Blueprint $table)
		{
			$table->dropForeign('purchase_requests_approve_by_foreign');
			$table->dropForeign('purchase_requests_company_id_foreign');
			$table->dropForeign('purchase_requests_create_by_foreign');
			$table->dropForeign('purchase_requests_reject_by_foreign');
			$table->dropForeign('purchase_requests_supplier_id_foreign');
			$table->dropForeign('purchase_requests_vm_id_foreign');
			$table->dropForeign('purchase_requests_warehouse_id_foreign');
		});
	}

}
