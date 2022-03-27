<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToStockAdjustmentDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('stock_adjustment_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('stock_adjustments')->onUpdate('RESTRICT')->onDelete('CASCADE');
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
		Schema::table('stock_adjustment_details', function(Blueprint $table)
		{
			$table->dropForeign('stock_adjustment_details_header_id_foreign');
			$table->dropForeign('stock_adjustment_details_item_id_foreign');
		});
	}

}
