<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToUsingItemDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('using_item_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('using_items')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_maintenance_detail_id')->references('id')->on('vehicle_maintenance_details')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('using_item_details', function(Blueprint $table)
		{
			$table->dropForeign('using_item_details_header_id_foreign');
			$table->dropForeign('using_item_details_item_id_foreign');
			$table->dropForeign('using_item_details_vehicle_maintenance_detail_id_foreign');
		});
	}

}
