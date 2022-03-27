<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPickingDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('picking_details', function(Blueprint $table)
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
		Schema::table('picking_details', function(Blueprint $table)
		{
			$table->dropForeign('picking_details_warehouse_receipt_detail_id_foreign');
		});
	}

}
