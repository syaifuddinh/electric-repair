<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPurchaseOrderReturnDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('purchase_order_return_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('purchase_order_returns')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('po_detail_id')->references('id')->on('purchase_order_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('purchase_order_return_details', function(Blueprint $table)
		{
			$table->dropForeign('purchase_order_return_details_header_id_foreign');
			$table->dropForeign('purchase_order_return_details_item_id_foreign');
			$table->dropForeign('purchase_order_return_details_po_detail_id_foreign');
		});
	}

}
