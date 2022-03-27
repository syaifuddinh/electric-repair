<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToAssetSalesDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('asset_sales_details', function(Blueprint $table)
		{
			$table->foreign('asset_id')->references('id')->on('assets')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('asset_sales')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('asset_sales_details', function(Blueprint $table)
		{
			$table->dropForeign('asset_sales_details_asset_id_foreign');
			$table->dropForeign('asset_sales_details_header_id_foreign');
		});
	}

}
