<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCustomerPricesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('customer_prices', function(Blueprint $table)
		{
			$table->foreign('combined_price_id')->references('id')->on('combined_prices')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('customer_prices', function(Blueprint $table)
		{
			$table->dropForeign('customer_prices_combined_price_id_foreign');
		});
	}

}
