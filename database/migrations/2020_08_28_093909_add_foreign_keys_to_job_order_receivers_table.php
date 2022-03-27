<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToJobOrderReceiversTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_order_receivers', function(Blueprint $table)
		{
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('job_orders')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_order_receivers', function(Blueprint $table)
		{
			$table->dropForeign('job_order_receivers_create_by_foreign');
			$table->dropForeign('job_order_receivers_header_id_foreign');
		});
	}

}
