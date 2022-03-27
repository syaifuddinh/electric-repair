<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToNotaCreditsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('nota_credits', function(Blueprint $table)
		{
			$table->foreign('cash_transaction_id')->references('id')->on('cash_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('contact_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('receivable_id')->references('id')->on('receivables')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('nota_credits', function(Blueprint $table)
		{
			$table->dropForeign('nota_credits_cash_transaction_id_foreign');
			$table->dropForeign('nota_credits_company_id_foreign');
			$table->dropForeign('nota_credits_contact_id_foreign');
			$table->dropForeign('nota_credits_journal_id_foreign');
			$table->dropForeign('nota_credits_receivable_id_foreign');
		});
	}

}
