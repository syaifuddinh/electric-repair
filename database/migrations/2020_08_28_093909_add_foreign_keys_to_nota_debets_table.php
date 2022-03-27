<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToNotaDebetsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('nota_debets', function(Blueprint $table)
		{
			$table->foreign('cash_transaction_id')->references('id')->on('cash_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('contact_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('payable_id')->references('id')->on('payables')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('nota_debets', function(Blueprint $table)
		{
			$table->dropForeign('nota_debets_cash_transaction_id_foreign');
			$table->dropForeign('nota_debets_company_id_foreign');
			$table->dropForeign('nota_debets_contact_id_foreign');
			$table->dropForeign('nota_debets_journal_id_foreign');
			$table->dropForeign('nota_debets_payable_id_foreign');
		});
	}

}
