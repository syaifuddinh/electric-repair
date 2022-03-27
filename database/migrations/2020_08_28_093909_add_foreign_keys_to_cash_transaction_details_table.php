<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCashTransactionDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cash_transaction_details', function(Blueprint $table)
		{
			$table->foreign('job_order_cost_id')->references('id')->on('job_order_costs')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('manifest_cost_id')->references('id')->on('manifest_costs')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cash_transaction_details', function(Blueprint $table)
		{
			$table->dropForeign('cash_transaction_details_job_order_cost_id_foreign');
			$table->dropForeign('cash_transaction_details_manifest_cost_id_foreign');
		});
	}

}
