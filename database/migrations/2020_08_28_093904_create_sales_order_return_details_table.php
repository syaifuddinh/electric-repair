<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSalesOrderReturnDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sales_order_return_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('item_id')->unsigned();
			$table->integer('item_detail_id')->unsigned()->nullable();
			$table->float('qty', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
			$table->timestamps();
			$table->float('receive', 10, 0)->default(0);
			$table->integer('sales_order_detail_id')->unsigned();
			$table->float('so_qty', 10, 0)->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('sales_order_return_details');
	}

}
