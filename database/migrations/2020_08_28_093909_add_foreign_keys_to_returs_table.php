<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToRetursTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('returs', function(Blueprint $table)
		{
			$table->foreign('account_cash_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('receipt_list_id')->references('id')->on('receipt_lists')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('supplier_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
		Schema::table('returs', function(Blueprint $table)
		{
			$table->dropForeign('returs_account_cash_id_foreign');
			$table->dropForeign('returs_company_id_foreign');
			$table->dropForeign('returs_create_by_foreign');
			$table->dropForeign('returs_journal_id_foreign');
			$table->dropForeign('returs_receipt_list_id_foreign');
			$table->dropForeign('returs_supplier_id_foreign');
			$table->dropForeign('returs_warehouse_id_foreign');
		});
	}

}
