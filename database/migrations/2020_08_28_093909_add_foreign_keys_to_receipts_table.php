<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToReceiptsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('receipts', function(Blueprint $table)
		{
			$table->foreign('account_item_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('item_migration_id')->references('id')->on('item_migrations')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('po_id')->references('id')->on('purchase_orders')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('po_return_id')->references('id')->on('purchase_order_returns')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('sales_return_id')->references('id')->on('sales_order_returns')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('usage_return_id')->references('id')->on('using_items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('receipts', function(Blueprint $table)
		{
			$table->dropForeign('receipts_account_item_id_foreign');
			$table->dropForeign('receipts_company_id_foreign');
			$table->dropForeign('receipts_item_migration_id_foreign');
			$table->dropForeign('receipts_journal_id_foreign');
			$table->dropForeign('receipts_po_id_foreign');
			$table->dropForeign('receipts_po_return_id_foreign');
			$table->dropForeign('receipts_sales_return_id_foreign');
			$table->dropForeign('receipts_usage_return_id_foreign');
		});
	}

}
