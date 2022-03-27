<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCompaniesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('companies', function(Blueprint $table)
		{
			$table->foreign('account_kasbon_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('area_id')->references('id')->on('areas')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('bank_account_id', 'companies_bank_for')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cash_account_id', 'companies_cash_for')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('city_id')->references('id')->on('cities')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('mutation_account_id', 'companies_mut_for')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('companies', function(Blueprint $table)
		{
			$table->dropForeign('companies_account_kasbon_id_foreign');
			$table->dropForeign('companies_area_id_foreign');
			$table->dropForeign('companies_bank_for');
			$table->dropForeign('companies_cash_for');
			$table->dropForeign('companies_city_id_foreign');
			$table->dropForeign('companies_mut_for');
		});
	}

}
