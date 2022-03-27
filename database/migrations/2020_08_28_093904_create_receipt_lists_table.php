<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReceiptListsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('receipt_lists', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('warehouse_id')->unsigned();
			$table->integer('cash_account_id')->unsigned()->nullable();
			$table->string('delivery_no', 191);
			$table->string('receive_name', 191);
			$table->string('description', 191)->nullable();
			$table->date('receive_date');
			$table->integer('termin')->default(1);
			$table->integer('urut');
			$table->timestamps();
			$table->boolean('type')->nullable()->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('receipt_lists');
	}

}
