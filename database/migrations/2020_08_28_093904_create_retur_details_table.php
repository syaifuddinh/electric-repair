<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReturDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('retur_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('receipt_list_detail_id')->unsigned();
			$table->integer('item_id')->unsigned();
			$table->float('qty_retur', 10, 0)->default(0);
			$table->float('leftover', 10, 0)->default(0);
			$table->float('receive', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
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
		Schema::drop('retur_details');
	}

}
