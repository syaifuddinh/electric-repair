<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePickingDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('picking_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id');
			$table->timestamps();
			$table->integer('item_id');
			$table->string('no_surat_jalan', 100)->nullable();
			$table->integer('qty');
			$table->integer('rack_id');
			$table->integer('warehouse_receipt_detail_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('picking_details');
	}

}
