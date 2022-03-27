<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePurchaseRequestDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('purchase_request_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('vehicle_id')->unsigned()->nullable();
			$table->integer('item_id')->unsigned();
			$table->float('qty', 10, 0)->default(0);
			$table->float('qty_approve', 10, 0)->default(0);
			$table->timestamps();
			$table->integer('vehicle_maintenance_detail_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('purchase_request_details');
	}

}
