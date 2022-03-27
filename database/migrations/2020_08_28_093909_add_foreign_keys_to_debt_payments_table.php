<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDebtPaymentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('debt_payments', function(Blueprint $table)
		{
			$table->foreign('cash_account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cek_giro_id')->references('id')->on('cek_giros')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('debts')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('SET NULL');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('debt_payments', function(Blueprint $table)
		{
			$table->dropForeign('debt_payments_cash_account_id_foreign');
			$table->dropForeign('debt_payments_cek_giro_id_foreign');
			$table->dropForeign('debt_payments_create_by_foreign');
			$table->dropForeign('debt_payments_header_id_foreign');
			$table->dropForeign('debt_payments_journal_id_foreign');
		});
	}

}
