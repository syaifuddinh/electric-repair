<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToJobOrderDetailOutputsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_order_detail_outputs', function(Blueprint $table)
		{
			$table->foreign('job_order_detail_id')->references('id')->on('job_order_details')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_order_detail_outputs', function(Blueprint $table)
		{
			$table->dropForeign('job_order_detail_outputs_job_order_detail_id_foreign');
		});
	}

}
