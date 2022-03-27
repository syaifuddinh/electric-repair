<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVendorPricesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vendor_prices', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('vendor_id')->unsigned();
			$table->integer('company_id')->unsigned();
			$table->integer('route_id')->unsigned()->nullable();
			$table->integer('service_id')->unsigned();
			$table->integer('service_type_id')->unsigned();
			$table->integer('commodity_id')->unsigned()->nullable();
			$table->integer('piece_id')->unsigned()->nullable();
			$table->integer('moda_id')->unsigned()->nullable();
			$table->integer('vehicle_type_id')->unsigned()->nullable();
			$table->integer('container_type_id')->unsigned()->nullable();
			$table->integer('rack_id')->unsigned()->nullable();
			$table->integer('created_by')->unsigned();
			$table->integer('updated_by')->unsigned()->nullable();
			$table->string('name', 191);
			$table->float('min_tonase', 10, 0)->nullable();
			$table->float('price_tonase', 10, 0)->nullable();
			$table->float('min_volume', 10, 0)->nullable();
			$table->float('price_volume', 10, 0)->nullable();
			$table->float('min_item', 10, 0)->nullable();
			$table->float('price_item', 10, 0)->nullable();
			$table->float('price_full', 10, 0)->nullable();
			$table->string('piece_name', 191)->nullable();
			$table->boolean('is_active')->default(1);
			$table->integer('imposition')->nullable()->default(0);
			$table->float('price_imposition', 10, 0)->nullable();
			$table->string('description', 191)->nullable();
			$table->float('price_handling_tonase', 10, 0)->nullable();
			$table->float('price_handling_volume', 10, 0)->nullable();
			$table->timestamps();
			$table->integer('cost_type_id')->unsigned()->index();
			$table->date('date')->index();
			$table->integer('cost_category')->default(0)->index();
			$table->integer('is_used')->default(1)->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('vendor_prices');
	}

}
