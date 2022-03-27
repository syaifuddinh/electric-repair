<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCashAdvanceStatusesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cash_advance_statuses', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('cash_advances')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cash_advance_statuses', function(Blueprint $table)
		{
			$table->dropForeign('cash_advance_statuses_header_id_foreign');
			$table->dropForeign('cash_advance_statuses_user_id_foreign');
		});
	}

}
