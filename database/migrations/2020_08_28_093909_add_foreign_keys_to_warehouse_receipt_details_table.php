<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToWarehouseReceiptDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('warehouse_receipt_details', function(Blueprint $table)
		{
			$table->foreign('pallet_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('warehouse_receipt_details', function(Blueprint $table)
		{
			$table->dropForeign('warehouse_receipt_details_pallet_id_foreign');
		});
	}

}
