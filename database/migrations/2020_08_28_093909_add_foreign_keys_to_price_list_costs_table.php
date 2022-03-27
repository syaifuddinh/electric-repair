<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPriceListCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('price_list_costs', function(Blueprint $table)
		{
			$table->foreign('cost_type_id')->references('id')->on('cost_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('price_lists')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vendor_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('price_list_costs', function(Blueprint $table)
		{
			$table->dropForeign('price_list_costs_cost_type_id_foreign');
			$table->dropForeign('price_list_costs_create_by_foreign');
			$table->dropForeign('price_list_costs_header_id_foreign');
			$table->dropForeign('price_list_costs_journal_id_foreign');
			$table->dropForeign('price_list_costs_vendor_id_foreign');
		});
	}

}
