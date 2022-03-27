<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAssetGroupsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('asset_groups', function(Blueprint $table)
		{
			$table->foreign('account_accumulation_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_asset_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_depreciation_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('asset_groups', function(Blueprint $table)
		{
			$table->dropForeign('asset_groups_account_accumulation_id_foreign');
			$table->dropForeign('asset_groups_account_asset_id_foreign');
			$table->dropForeign('asset_groups_account_depreciation_id_foreign');
		});
	}

}
