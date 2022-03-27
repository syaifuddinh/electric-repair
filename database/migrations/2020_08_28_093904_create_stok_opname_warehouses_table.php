<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStokOpnameWarehousesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stok_opname_warehouses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_receipt_id')->unsigned()->nullable();
			$table->integer('type')->unsigned();
			$table->string('code', 200);
			$table->date('date_transactions');
			$table->integer('customer_id')->unsigned()->nullable();
			$table->integer('warehouse_id')->unsigned();
			$table->integer('status')->default(0);
			$table->timestamps();
			$table->integer('created_by')->unsigned();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('stok_opname_warehouses');
	}

}
