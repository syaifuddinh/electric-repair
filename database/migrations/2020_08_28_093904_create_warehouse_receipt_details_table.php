<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWarehouseReceiptDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('warehouse_receipt_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('rack_id')->unsigned();
			$table->integer('piece_id')->unsigned()->nullable();
			$table->integer('vehicle_type_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->float('qty', 10, 0)->default(0);
			$table->float('weight', 10, 0)->default(0);
			$table->float('volume', 10, 0)->default(0);
			$table->float('long', 10, 0)->default(0);
			$table->float('wide', 10, 0)->default(0);
			$table->float('high', 10, 0)->default(0);
			$table->string('item_name', 191)->nullable();
			$table->string('barcode', 80);
			$table->integer('imposition')->default(1);
			$table->string('nopol', 191)->nullable();
			$table->string('driver_name', 191)->nullable();
			$table->string('phone_number', 191)->nullable();
			$table->timestamps();
			$table->float('leftover_warehouse', 10, 0)->default(0);
			$table->float('leftover_stuffing', 10, 0)->default(0);
			$table->float('weight_per_kg', 10, 0)->default(0);
			$table->float('volume_per_meter', 10, 0)->default(0);
			$table->date('date_out')->nullable();
			$table->string('storage_type', 20)->default('RACK');
			$table->integer('is_exists')->default(0);
			$table->integer('item_id')->nullable();
			$table->integer('is_use_pallet')->default(0);
			$table->integer('pallet_id')->unsigned()->nullable();
			$table->integer('pallet_qty')->default(0);
			$table->string('kemasan', 100)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('warehouse_receipt_details');
	}

}
