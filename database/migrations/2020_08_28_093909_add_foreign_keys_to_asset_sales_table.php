<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAssetSalesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('asset_sales', function(Blueprint $table)
		{
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cash_account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('costumer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('sales_account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('asset_sales', function(Blueprint $table)
		{
			$table->dropForeign('asset_sales_approve_by_foreign');
			$table->dropForeign('asset_sales_cash_account_id_foreign');
			$table->dropForeign('asset_sales_company_id_foreign');
			$table->dropForeign('asset_sales_costumer_id_foreign');
			$table->dropForeign('asset_sales_create_by_foreign');
			$table->dropForeign('asset_sales_journal_id_foreign');
			$table->dropForeign('asset_sales_sales_account_id_foreign');
		});
	}

}
