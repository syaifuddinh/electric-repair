<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToSalesOrderReturnsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('sales_order_returns', function(Blueprint $table)
		{
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cancel_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('sales_order_id')->references('id')->on('sales_orders')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
		Schema::table('sales_order_returns', function(Blueprint $table)
		{
			$table->dropForeign('sales_order_returns_approve_by_foreign');
			$table->dropForeign('sales_order_returns_cancel_by_foreign');
			$table->dropForeign('sales_order_returns_create_by_foreign');
			$table->dropForeign('sales_order_returns_journal_id_foreign');
			$table->dropForeign('sales_order_returns_sales_order_id_foreign');
			$table->dropForeign('sales_order_returns_warehouse_id_foreign');
		});
	}

}
