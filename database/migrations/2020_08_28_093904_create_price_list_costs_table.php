<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePriceListCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('price_list_costs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('cost_type_id')->unsigned()->index();
			$table->integer('vendor_id')->unsigned()->nullable()->index();
			$table->integer('journal_id')->unsigned()->nullable()->index();
			$table->integer('create_by')->unsigned()->index();
			$table->integer('type')->unsigned()->default(1)->index();
			$table->float('qty', 10)->default(0.00)->index();
			$table->float('price', 10)->default(0.00)->index();
			$table->float('total_price', 10)->default(0.00)->index();
			$table->text('description', 65535)->nullable();
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('price_list_costs');
	}

}
