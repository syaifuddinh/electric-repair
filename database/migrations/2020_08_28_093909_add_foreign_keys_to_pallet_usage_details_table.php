<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPalletUsageDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('pallet_usage_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('pallet_usages')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('pallet_usage_details', function(Blueprint $table)
		{
			$table->dropForeign('pallet_usage_details_header_id_foreign');
			$table->dropForeign('pallet_usage_details_item_id_foreign');
		});
	}

}
