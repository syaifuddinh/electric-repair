<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePriceListsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('price_lists', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('route_id')->unsigned()->nullable();
			$table->integer('service_id')->unsigned();
			$table->integer('combined_price_id')->unsigned()->nullable();
			$table->string('price_type', 10)->default('service');
			$table->integer('commodity_id')->unsigned()->nullable();
			$table->integer('moda_id')->unsigned()->nullable();
			$table->integer('vehicle_type_id')->unsigned()->nullable();
			$table->integer('piece_id')->unsigned()->nullable();
			$table->integer('created_by')->unsigned();
			$table->string('code', 191);
			$table->string('name', 191);
			$table->float('min_tonase', 10, 0)->nullable();
			$table->float('price_tonase', 10, 0)->nullable();
			$table->float('min_volume', 10, 0)->nullable();
			$table->float('price_volume', 10, 0)->nullable();
			$table->float('min_item', 10, 0)->nullable();
			$table->float('price_item', 10, 0)->nullable();
			$table->float('price_full', 10, 0)->nullable();
			$table->float('is_active', 10, 0)->default(1);
			$table->string('piece_name', 191)->nullable();
			$table->string('description', 191)->nullable();
			$table->timestamps();
			$table->float('price_handling_tonase', 10, 0)->nullable();
			$table->integer('rack_id')->unsigned()->nullable();
			$table->integer('container_type_id')->unsigned()->nullable();
			$table->integer('service_type_id')->unsigned()->nullable();
			$table->float('price_handling_volume', 10, 0)->nullable();
			$table->integer('warehouse_id')->unsigned()->nullable();
			$table->integer('is_warehouse')->default(0);
			$table->float('price_borongan', 10, 0)->default(0);
			$table->integer('min_borongan')->default(0);
			$table->integer('free_storage_day')->default(0);
			$table->float('over_storage_price', 10, 0)->default(0);
			$table->float('daily_price', 10, 0)->default(0);
			$table->float('pallet_price', 10, 0)->default(0);
			$table->integer('handling_type')->default(1)->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('price_lists');
	}

}
