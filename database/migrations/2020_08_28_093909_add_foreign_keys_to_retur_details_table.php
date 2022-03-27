<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToReturDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('retur_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('returs')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('item_id')->references('id')->on('items')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('receipt_list_detail_id')->references('id')->on('receipt_list_details')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('retur_details', function(Blueprint $table)
		{
			$table->dropForeign('retur_details_header_id_foreign');
			$table->dropForeign('retur_details_item_id_foreign');
			$table->dropForeign('retur_details_receipt_list_detail_id_foreign');
		});
	}

}
