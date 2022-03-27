<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsingItemDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('using_item_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('vehicle_maintenance_detail_id')->unsigned()->nullable();
			$table->integer('item_id')->unsigned();
			$table->float('qty', 10, 0)->default(0);
			$table->float('cost', 10, 0)->default(0);
			$table->float('total', 10, 0)->default(0);
			$table->float('used', 10, 0)->default(0);
			$table->timestamps();
			$table->float('receive', 10, 0)->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('using_item_details');
	}

}
