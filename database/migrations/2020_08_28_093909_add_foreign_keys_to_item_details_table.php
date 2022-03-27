<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToItemDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('item_details', function(Blueprint $table)
		{
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('po_id')->references('id')->on('purchase_orders')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('rack_id')->references('id')->on('racks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('item_details', function(Blueprint $table)
		{
			$table->dropForeign('item_details_item_id_foreign');
			$table->dropForeign('item_details_po_id_foreign');
			$table->dropForeign('item_details_rack_id_foreign');
			$table->dropForeign('item_details_warehouse_id_foreign');
		});
	}

}
