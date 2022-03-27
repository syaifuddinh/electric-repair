<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAssetPurchasesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('asset_purchases', function(Blueprint $table)
		{
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cash_account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('supplier_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('asset_purchases', function(Blueprint $table)
		{
			$table->dropForeign('asset_purchases_approve_by_foreign');
			$table->dropForeign('asset_purchases_cash_account_id_foreign');
			$table->dropForeign('asset_purchases_company_id_foreign');
			$table->dropForeign('asset_purchases_create_by_foreign');
			$table->dropForeign('asset_purchases_journal_id_foreign');
			$table->dropForeign('asset_purchases_supplier_id_foreign');
		});
	}

}
