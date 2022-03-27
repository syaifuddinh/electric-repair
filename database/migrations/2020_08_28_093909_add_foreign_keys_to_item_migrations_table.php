<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToItemMigrationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('item_migrations', function(Blueprint $table)
		{
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cancel_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('rack_from_id')->references('id')->on('racks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('rack_to_id')->references('id')->on('racks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_from_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_receipt_id')->references('id')->on('warehouse_receipts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_to_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('item_migrations', function(Blueprint $table)
		{
			$table->dropForeign('item_migrations_approve_by_foreign');
			$table->dropForeign('item_migrations_cancel_by_foreign');
			$table->dropForeign('item_migrations_create_by_foreign');
			$table->dropForeign('item_migrations_rack_from_id_foreign');
			$table->dropForeign('item_migrations_rack_to_id_foreign');
			$table->dropForeign('item_migrations_warehouse_from_id_foreign');
			$table->dropForeign('item_migrations_warehouse_receipt_id_foreign');
			$table->dropForeign('item_migrations_warehouse_to_id_foreign');
		});
	}

}
