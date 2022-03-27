<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToStokOpnameWarehousesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('stok_opname_warehouses', function(Blueprint $table)
		{
			$table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_receipt_id')->references('id')->on('warehouse_receipts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('stok_opname_warehouses', function(Blueprint $table)
		{
			$table->dropForeign('stok_opname_warehouses_created_by_foreign');
			$table->dropForeign('stok_opname_warehouses_customer_id_foreign');
			$table->dropForeign('stok_opname_warehouses_warehouse_id_foreign');
			$table->dropForeign('stok_opname_warehouses_warehouse_receipt_id_foreign');
		});
	}

}
