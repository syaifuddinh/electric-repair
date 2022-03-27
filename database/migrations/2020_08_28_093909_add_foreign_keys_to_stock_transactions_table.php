<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToStockTransactionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('stock_transactions', function(Blueprint $table)
		{
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('stock_transactions', function(Blueprint $table)
		{
			$table->dropForeign('stock_transactions_customer_id_foreign');
			$table->dropForeign('stock_transactions_warehouse_receipt_detail_id_foreign');
		});
	}

}
