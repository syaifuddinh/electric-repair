<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToReceiptListDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('receipt_list_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('receipt_lists')->onUpdate('RESTRICT')->onDelete('CASCADE');
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
		Schema::table('receipt_list_details', function(Blueprint $table)
		{
			$table->dropForeign('receipt_list_details_header_id_foreign');
			$table->dropForeign('receipt_list_details_item_id_foreign');
		});
	}

}
