<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCashTransactionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cash_transactions', function(Blueprint $table)
		{
			$table->foreign('account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('parent_id')->references('id')->on('cash_transactions')->onUpdate('RESTRICT')->onDelete('CASCADE');
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
		Schema::table('cash_transactions', function(Blueprint $table)
		{
			$table->dropForeign('cash_transactions_account_id_foreign');
			$table->dropForeign('cash_transactions_company_id_foreign');
			$table->dropForeign('cash_transactions_created_by_foreign');
			$table->dropForeign('cash_transactions_journal_id_foreign');
			$table->dropForeign('cash_transactions_parent_id_foreign');
			$table->dropForeign('cash_transactions_type_transaction_id_foreign');
		});
	}

}
