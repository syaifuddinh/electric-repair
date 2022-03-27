<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('invoices', function(Blueprint $table)
		{
			$table->foreign('account_cash_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_receivable_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_selling_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('SET NULL')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('receivable_id')->references('id')->on('receivables')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('tax_id')->references('id')->on('taxes')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('type_transaction_id')->references('id')->on('type_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('invoices', function(Blueprint $table)
		{
			$table->dropForeign('invoices_account_cash_id_foreign');
			$table->dropForeign('invoices_account_receivable_id_foreign');
			$table->dropForeign('invoices_account_selling_id_foreign');
			$table->dropForeign('invoices_approve_by_foreign');
			$table->dropForeign('invoices_company_id_foreign');
			$table->dropForeign('invoices_create_by_foreign');
			$table->dropForeign('invoices_customer_id_foreign');
			$table->dropForeign('invoices_journal_id_foreign');
			$table->dropForeign('invoices_receivable_id_foreign');
			$table->dropForeign('invoices_tax_id_foreign');
			$table->dropForeign('invoices_type_transaction_id_foreign');
		});
	}

}
