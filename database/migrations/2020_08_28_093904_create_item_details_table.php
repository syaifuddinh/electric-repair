<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateItemDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('item_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('item_id')->unsigned();
			$table->integer('warehouse_id')->unsigned();
			$table->integer('rack_id')->unsigned()->nullable();
			$table->integer('po_id')->unsigned()->nullable();
			$table->integer('deletion_id')->unsigned()->nullable();
			$table->string('batch_no', 191);
			$table->boolean('is_active')->default(1);
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('item_details');
	}

}
