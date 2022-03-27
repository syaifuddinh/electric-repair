<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToStokOpnameWarehouseDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('stok_opname_warehouse_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('stok_opname_warehouses')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('rack_id')->references('id')->on('racks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_receipt_id')->references('id')->on('warehouse_receipts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_stock_detail_id')->references('id')->on('warehouse_stock_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('stok_opname_warehouse_details', function(Blueprint $table)
		{
			$table->dropForeign('stok_opname_warehouse_details_header_id_foreign');
			$table->dropForeign('stok_opname_warehouse_details_rack_id_foreign');
			$table->dropForeign('stok_opname_warehouse_details_warehouse_receipt_id_foreign');
			$table->dropForeign('stok_opname_warehouse_details_warehouse_stock_detail_id_foreign');
		});
	}

}
