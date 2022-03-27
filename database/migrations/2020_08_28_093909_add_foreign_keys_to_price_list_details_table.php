<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPriceListDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('price_list_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('price_lists')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('service_id')->references('id')->on('services')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('price_list_details', function(Blueprint $table)
		{
			$table->dropForeign('price_list_details_header_id_foreign');
			$table->dropForeign('price_list_details_service_id_foreign');
		});
	}

}
