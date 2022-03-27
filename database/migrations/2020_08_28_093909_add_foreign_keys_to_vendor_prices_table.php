<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToVendorPricesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vendor_prices', function(Blueprint $table)
		{
			$table->foreign('cost_type_id')->references('id')->on('cost_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vendor_prices', function(Blueprint $table)
		{
			$table->dropForeign('vendor_prices_cost_type_id_foreign');
		});
	}

}
