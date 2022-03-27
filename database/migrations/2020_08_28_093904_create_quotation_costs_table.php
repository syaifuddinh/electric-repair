<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQuotationCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quotation_costs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('cost_type_id')->unsigned();
			$table->integer('vendor_id')->unsigned();
			$table->integer('created_by')->unsigned();
			$table->float('total', 10, 0)->default(0);
			$table->float('cost', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
			$table->boolean('is_internal')->default(1);
			$table->timestamps();
			$table->integer('quotation_detail_id')->unsigned()->nullable();
			$table->integer('route_cost_id')->unsigned()->nullable();
			$table->float('total_cost', 10, 0)->nullable()->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('quotation_costs');
	}

}
