<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCashAdvancesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cash_advances', function(Blueprint $table)
		{
			$table->foreign('account_advance_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_cash_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cancel_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cash_transaction_id')->references('id')->on('cash_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('employee_id', 'cash_advances_driver_id_foreign')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('paid_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('realisation_id')->references('id')->on('cash_advances')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('update_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cash_advances', function(Blueprint $table)
		{
			$table->dropForeign('cash_advances_account_advance_id_foreign');
			$table->dropForeign('cash_advances_account_cash_id_foreign');
			$table->dropForeign('cash_advances_approve_by_foreign');
			$table->dropForeign('cash_advances_cancel_by_foreign');
			$table->dropForeign('cash_advances_cash_transaction_id_foreign');
			$table->dropForeign('cash_advances_company_id_foreign');
			$table->dropForeign('cash_advances_create_by_foreign');
			$table->dropForeign('cash_advances_driver_id_foreign');
			$table->dropForeign('cash_advances_journal_id_foreign');
			$table->dropForeign('cash_advances_paid_by_foreign');
			$table->dropForeign('cash_advances_realisation_id_foreign');
			$table->dropForeign('cash_advances_update_by_foreign');
		});
	}

}
