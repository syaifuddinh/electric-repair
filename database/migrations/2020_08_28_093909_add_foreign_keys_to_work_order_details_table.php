<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToWorkOrderDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('work_order_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('work_orders')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('price_list_id')->references('id')->on('price_lists')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('quotation_detail_id')->references('id')->on('quotation_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('work_order_details', function(Blueprint $table)
		{
			$table->dropForeign('work_order_details_header_id_foreign');
			$table->dropForeign('work_order_details_price_list_id_foreign');
			$table->dropForeign('work_order_details_quotation_detail_id_foreign');
		});
	}

}
