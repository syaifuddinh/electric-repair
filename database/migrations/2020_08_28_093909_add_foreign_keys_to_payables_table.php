<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPayablesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('payables', function(Blueprint $table)
		{
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('contact_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('CASCADE');
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
		Schema::table('payables', function(Blueprint $table)
		{
			$table->dropForeign('payables_company_id_foreign');
			$table->dropForeign('payables_contact_id_foreign');
			$table->dropForeign('payables_created_by_foreign');
			$table->dropForeign('payables_journal_id_foreign');
			$table->dropForeign('payables_type_transaction_id_foreign');
		});
	}

}
