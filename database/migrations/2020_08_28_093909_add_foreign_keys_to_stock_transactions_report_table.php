<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToStockTransactionsReportTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('stock_transactions_report', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('stock_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('stock_transactions_report', function(Blueprint $table)
		{
			$table->dropForeign('stock_transactions_report_header_id_foreign');
		});
	}

}
