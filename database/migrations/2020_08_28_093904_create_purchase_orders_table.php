<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePurchaseOrdersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('purchase_orders', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('purchase_request_id')->unsigned();
			$table->integer('company_id')->unsigned();
			$table->integer('warehouse_id')->unsigned()->nullable();
			$table->integer('supplier_id')->unsigned();
			$table->integer('po_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->date('po_date');
			$table->integer('po_status');
			$table->timestamps();
			$table->integer('vehicle_maintenance_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('purchase_orders');
	}

}
