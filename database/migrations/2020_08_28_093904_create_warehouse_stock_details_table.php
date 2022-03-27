<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWarehouseStockDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('warehouse_stock_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('rack_id')->unsigned();
			$table->integer('item_id')->unsigned();
			$table->integer('qty');
			$table->timestamps();
			$table->integer('pending_item_qty')->default(0);
			$table->string('no_surat_jalan', 100)->nullable();
			$table->integer('customer_id')->unsigned()->nullable();
			$table->integer('warehouse_receipt_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('warehouse_stock_details');
	}

}
