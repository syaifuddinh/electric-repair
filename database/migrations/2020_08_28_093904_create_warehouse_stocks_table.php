<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWarehouseStocksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('warehouse_stocks', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_id')->unsigned();
			$table->integer('item_id')->unsigned();
			$table->float('qty', 10, 0)->default(0);
			$table->timestamps();
			$table->float('transit', 10, 0)->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('warehouse_stocks');
	}

}
