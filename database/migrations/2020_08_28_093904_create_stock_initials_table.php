<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateStockInitialsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('stock_initials', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('warehouse_id')->unsigned();
			$table->integer('item_id')->unsigned();
			$table->integer('stock_transaction_id')->unsigned()->nullable();
			$table->date('date_transaction');
			$table->string('code', 191)->nullable();
			$table->float('qty', 10, 0);
			$table->float('price', 10, 0);
			$table->float('total', 10, 0);
			$table->string('description', 191)->nullable();
			$table->timestamps();
			$table->integer('create_by')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('stock_initials');
	}

}
