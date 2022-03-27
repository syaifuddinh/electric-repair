<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToJobPacketsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_packets', function(Blueprint $table)
		{
			$table->foreign('job_order_id')->references('id')->on('job_orders')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('work_order_detail_id')->references('id')->on('work_order_details')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_packets', function(Blueprint $table)
		{
			$table->dropForeign('job_packets_job_order_id_foreign');
			$table->dropForeign('job_packets_work_order_detail_id_foreign');
		});
	}

}
