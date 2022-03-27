<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePackagingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('packagings', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_id');
			$table->integer('job_order_id');
			$table->string('item_name', 150);
			$table->string('barcode', 150);
			$table->integer('status')->default(0);
			$table->text('description', 65535);
			$table->timestamps();
			$table->string('staff_gudang_name', 150);
			$table->float('price', 10, 0)->default(0);
			$table->integer('qty')->default(0);
			$table->time('start_time')->nullable();
			$table->time('end_time')->nullable();
			$table->integer('is_overtime')->default(0);
			$table->integer('is_handling_area')->default(0);
			$table->integer('is_picking_area')->default(0);
			$table->integer('rack_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('packagings');
	}

}
