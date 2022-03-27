<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAssetAfkirsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('asset_afkirs', function(Blueprint $table)
		{
			$table->foreign('account_loss_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('asset_id')->references('id')->on('assets')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
		Schema::table('asset_afkirs', function(Blueprint $table)
		{
			$table->dropForeign('asset_afkirs_account_loss_id_foreign');
			$table->dropForeign('asset_afkirs_approve_by_foreign');
			$table->dropForeign('asset_afkirs_asset_id_foreign');
			$table->dropForeign('asset_afkirs_company_id_foreign');
			$table->dropForeign('asset_afkirs_create_by_foreign');
			$table->dropForeign('asset_afkirs_journal_id_foreign');
		});
	}

}
