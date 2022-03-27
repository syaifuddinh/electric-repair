<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRouteCostDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('route_cost_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('cost_type_id')->unsigned();
			$table->integer('created_by')->unsigned();
			$table->float('cost', 10, 0)->default(0);
			$table->integer('total_liter')->default(0);
			$table->boolean('is_bbm');
			$table->boolean('is_internal');
			$table->float('harga_satuan', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
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
		Schema::drop('route_cost_details');
	}

}
