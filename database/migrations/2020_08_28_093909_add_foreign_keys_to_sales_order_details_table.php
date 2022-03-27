<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToSalesOrderDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('sales_order_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('sales_orders')->onUpdate('RESTRICT')->onDelete('CASCADE');
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
		Schema::table('sales_order_details', function(Blueprint $table)
		{
			$table->dropForeign('sales_order_details_header_id_foreign');
			$table->dropForeign('sales_order_details_item_id_foreign');
		});
	}

}
