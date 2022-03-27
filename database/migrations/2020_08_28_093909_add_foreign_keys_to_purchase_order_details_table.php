<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPurchaseOrderDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('purchase_order_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('purchase_orders')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('purchase_request_detail_id')->references('id')->on('purchase_request_details')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('purchase_order_details', function(Blueprint $table)
		{
			$table->dropForeign('purchase_order_details_header_id_foreign');
			$table->dropForeign('purchase_order_details_item_id_foreign');
			$table->dropForeign('purchase_order_details_purchase_request_detail_id_foreign');
		});
	}

}
