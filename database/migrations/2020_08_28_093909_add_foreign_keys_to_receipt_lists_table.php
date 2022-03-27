<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToReceiptListsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('receipt_lists', function(Blueprint $table)
		{
			$table->foreign('cash_account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('receipts')->onUpdate('RESTRICT')->onDelete('CASCADE');
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
		Schema::table('receipt_lists', function(Blueprint $table)
		{
			$table->dropForeign('receipt_lists_cash_account_id_foreign');
			$table->dropForeign('receipt_lists_header_id_foreign');
			$table->dropForeign('receipt_lists_warehouse_id_foreign');
		});
	}

}
