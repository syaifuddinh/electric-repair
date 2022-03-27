<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCombinedPriceDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('combined_price_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('combined_prices')->onUpdate('RESTRICT')->onDelete('CASCADE');
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
		Schema::table('combined_price_details', function(Blueprint $table)
		{
			$table->dropForeign('combined_price_details_header_id_foreign');
			$table->dropForeign('combined_price_details_service_id_foreign');
		});
	}

}
