<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToReturReceiptDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('retur_receipt_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('retur_receipts')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('retur_detail_id')->references('id')->on('retur_details')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('retur_receipt_details', function(Blueprint $table)
		{
			$table->dropForeign('retur_receipt_details_header_id_foreign');
			$table->dropForeign('retur_receipt_details_item_id_foreign');
			$table->dropForeign('retur_receipt_details_retur_detail_id_foreign');
		});
	}

}
