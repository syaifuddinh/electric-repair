<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPriceListsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('price_lists', function(Blueprint $table)
		{
			$table->foreign('container_type_id', 'price_list_ctype_for')->references('id')->on('container_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('rack_id', 'price_list_rack_id_for')->references('id')->on('racks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('service_type_id', 'price_list_service_type_for')->references('id')->on('service_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('combined_price_id')->references('id')->on('combined_prices')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('commodity_id')->references('id')->on('commodities')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('moda_id')->references('id')->on('modas')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('piece_id')->references('id')->on('pieces')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('route_id')->references('id')->on('routes')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('service_id')->references('id')->on('services')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('price_lists', function(Blueprint $table)
		{
			$table->dropForeign('price_list_ctype_for');
			$table->dropForeign('price_list_rack_id_for');
			$table->dropForeign('price_list_service_type_for');
			$table->dropForeign('price_lists_combined_price_id_foreign');
			$table->dropForeign('price_lists_commodity_id_foreign');
			$table->dropForeign('price_lists_company_id_foreign');
			$table->dropForeign('price_lists_created_by_foreign');
			$table->dropForeign('price_lists_moda_id_foreign');
			$table->dropForeign('price_lists_piece_id_foreign');
			$table->dropForeign('price_lists_route_id_foreign');
			$table->dropForeign('price_lists_service_id_foreign');
			$table->dropForeign('price_lists_vehicle_type_id_foreign');
			$table->dropForeign('price_lists_warehouse_id_foreign');
		});
	}

}
