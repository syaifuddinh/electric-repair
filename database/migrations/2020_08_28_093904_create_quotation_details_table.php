<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQuotationDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quotation_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('price_list_id')->unsigned()->nullable();
			$table->integer('route_id')->unsigned()->nullable();
			$table->integer('service_id')->unsigned()->nullable();
			$table->integer('combined_price_id')->unsigned()->nullable();
			$table->string('price_type', 10)->default('service');
			$table->integer('piece_id')->unsigned()->nullable();
			$table->integer('commodity_id')->unsigned()->nullable();
			$table->integer('moda_id')->unsigned()->nullable();
			$table->integer('vehicle_type_id')->unsigned()->nullable();
			$table->string('piece_name', 191)->nullable();
			$table->string('description', 191)->nullable();
			$table->integer('imposition')->nullable()->default(0);
			$table->float('total', 10, 0)->nullable();
			$table->float('price_inquery_tonase', 10, 0)->nullable();
			$table->float('price_contract_tonase', 10, 0)->nullable();
			$table->float('price_inquery_volume', 10, 0)->nullable();
			$table->float('price_contract_volume', 10, 0)->nullable();
			$table->float('price_inquery_item', 10, 0)->nullable();
			$table->float('price_contract_item', 10, 0)->nullable();
			$table->float('price_inquery_full', 10, 0)->nullable();
			$table->float('price_contract_full', 10, 0)->nullable();
			$table->float('cost', 10, 0)->nullable();
			$table->integer('is_generate')->nullable();
			$table->timestamps();
			$table->float('price_inquery_handling_tonase', 10, 0)->nullable();
			$table->float('price_contract_handling_tonase', 10, 0)->nullable();
			$table->float('price_inquery_handling_volume', 10, 0)->nullable();
			$table->float('price_contract_handling_volume', 10, 0)->nullable();
			$table->integer('rack_id')->unsigned()->nullable();
			$table->integer('container_type_id')->unsigned()->nullable();
			$table->integer('service_type_id')->unsigned()->nullable();
			$table->float('price_inquery_min_tonase', 10, 0)->nullable();
			$table->float('price_contract_min_tonase', 10, 0)->nullable();
			$table->float('price_inquery_min_volume', 10, 0)->nullable();
			$table->float('price_contract_min_volume', 10, 0)->nullable();
			$table->float('price_inquery_min_item', 10, 0)->nullable();
			$table->float('price_contract_min_item', 10, 0)->nullable();
			$table->integer('route_cost_id')->unsigned()->nullable();
			$table->float('price_list_price_full', 10, 0)->default(0);
			$table->float('price_list_price_tonase', 10, 0)->default(0);
			$table->float('price_list_price_volume', 10, 0)->default(0);
			$table->float('price_list_price_item', 10, 0)->default(0);
			$table->boolean('is_approve')->default(0);
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('warehouse_id')->unsigned()->nullable();
			$table->string('slug', 191)->nullable();
			$table->integer('free_storage_day')->default(0);
			$table->float('over_storage_price', 10, 0)->default(0);
			$table->float('pallet_price', 10, 0)->default(0);
			$table->integer('handling_type')
            ->nullable(true)
            ->default(1)
            ->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('quotation_details');
	}

}
