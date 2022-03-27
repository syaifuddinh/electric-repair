<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToQuotationDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quotation_details', function(Blueprint $table)
		{
			$table->foreign('container_type_id', 'quot_det_cont_type_for')->references('id')->on('container_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('rack_id', 'quot_det_rack_id_for')->references('id')->on('racks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('service_type_id', 'quot_det_ser_type_for')->references('id')->on('service_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('combined_price_id')->references('id')->on('combined_prices')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('commodity_id')->references('id')->on('commodities')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('quotations')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('moda_id')->references('id')->on('modas')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('piece_id')->references('id')->on('pieces')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('price_list_id')->references('id')->on('price_lists')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
		Schema::table('quotation_details', function(Blueprint $table)
		{
			$table->dropForeign('quot_det_cont_type_for');
			$table->dropForeign('quot_det_rack_id_for');
			$table->dropForeign('quot_det_ser_type_for');
			$table->dropForeign('quotation_details_approve_by_foreign');
			$table->dropForeign('quotation_details_combined_price_id_foreign');
			$table->dropForeign('quotation_details_commodity_id_foreign');
			$table->dropForeign('quotation_details_header_id_foreign');
			$table->dropForeign('quotation_details_moda_id_foreign');
			$table->dropForeign('quotation_details_piece_id_foreign');
			$table->dropForeign('quotation_details_price_list_id_foreign');
			$table->dropForeign('quotation_details_route_id_foreign');
			$table->dropForeign('quotation_details_service_id_foreign');
			$table->dropForeign('quotation_details_vehicle_type_id_foreign');
			$table->dropForeign('quotation_details_warehouse_id_foreign');
		});
	}

}
