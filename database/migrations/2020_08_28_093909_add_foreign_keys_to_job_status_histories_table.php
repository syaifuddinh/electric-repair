<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToJobStatusHistoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_status_histories', function(Blueprint $table)
		{
			$table->foreign('delivery_id')->references('id')->on('delivery_order_drivers')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('job_status_id')->references('id')->on('job_statuses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_status_histories', function(Blueprint $table)
		{
			$table->dropForeign('job_status_histories_delivery_id_foreign');
			$table->dropForeign('job_status_histories_job_status_id_foreign');
		});
	}

}
