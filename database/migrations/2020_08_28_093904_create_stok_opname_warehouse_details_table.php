<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStokOpnameWarehouseDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stok_opname_warehouse_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('item_id')->unsigned();
			$table->integer('warehouse_stock_detail_id')->unsigned();
			$table->integer('stock_sistem');
			$table->integer('stock_riil');
			$table->timestamps();
			$table->integer('rack_id')->unsigned();
			$table->string('no_surat_jalan', 300);
			$table->integer('warehouse_receipt_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('stok_opname_warehouse_details');
	}

}
