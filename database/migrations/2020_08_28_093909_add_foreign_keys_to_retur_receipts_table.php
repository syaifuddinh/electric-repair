<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToReturReceiptsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('retur_receipts', function(Blueprint $table)
		{
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('returs')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('warehouse_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('retur_receipts', function(Blueprint $table)
		{
			$table->dropForeign('retur_receipts_company_id_foreign');
			$table->dropForeign('retur_receipts_create_by_foreign');
			$table->dropForeign('retur_receipts_header_id_foreign');
			$table->dropForeign('retur_receipts_warehouse_id_foreign');
		});
	}

}
