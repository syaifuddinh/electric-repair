<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCashCountDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cash_count_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('cash_counts')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cash_count_details', function(Blueprint $table)
		{
			$table->dropForeign('cash_count_details_header_id_foreign');
		});
	}

}
