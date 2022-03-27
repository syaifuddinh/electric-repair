<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToWarehouseStockDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('warehouse_stock_details', function(Blueprint $table)
		{
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
		Schema::table('warehouse_stock_details', function(Blueprint $table)
		{
			$table->dropForeign('warehouse_stock_details_customer_id_foreign');
			$table->dropForeign('warehouse_stock_details_item_id_foreign');
			$table->dropForeign('warehouse_stock_details_warehouse_receipt_id_foreign');
		});
	}

}
