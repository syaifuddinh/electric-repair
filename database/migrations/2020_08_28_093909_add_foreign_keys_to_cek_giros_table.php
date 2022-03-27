<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCekGirosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cek_giros', function(Blueprint $table)
		{
			$table->foreign('account_bank_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('bank_id')->references('id')->on('banks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('kliring_account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('penerbit_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('penerima_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cek_giros', function(Blueprint $table)
		{
			$table->dropForeign('cek_giros_account_bank_id_foreign');
			$table->dropForeign('cek_giros_bank_id_foreign');
			$table->dropForeign('cek_giros_company_id_foreign');
			$table->dropForeign('cek_giros_journal_id_foreign');
			$table->dropForeign('cek_giros_kliring_account_id_foreign');
			$table->dropForeign('cek_giros_penerbit_id_foreign');
			$table->dropForeign('cek_giros_penerima_id_foreign');
		});
	}

}
