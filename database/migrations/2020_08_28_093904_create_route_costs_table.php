<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRouteCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('route_costs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('route_id')->unsigned();
			$table->integer('commodity_id')->unsigned();
			$table->integer('created_by')->unsigned();
			$table->float('cost', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
			$table->timestamps();
			$table->integer('vehicle_type_id')->unsigned()->nullable();
			$table->integer('header_id')->unsigned()->nullable();
			$table->integer('container_type_id')->unsigned()->nullable();
			$table->boolean('is_container')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('route_costs');
	}

}
