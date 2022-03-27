<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToItemMigrationDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('item_migration_details', function(Blueprint $table)
		{
			$table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('item_migration_details', function(Blueprint $table)
		{
			$table->dropForeign('item_migration_details_warehouse_receipt_detail_id_foreign');
		});
	}

}
