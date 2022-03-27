<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSalesOrderReturnsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('sales_order_returns', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('sales_order_id')->unsigned();
			$table->integer('warehouse_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('cancel_by')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->date('date_transaction');
			$table->date('date_approve')->nullable();
			$table->date('date_cancel')->nullable();
			$table->string('code', 191);
			$table->integer('status')->default(1);
			$table->text('description', 65535)->nullable();
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
		Schema::drop('sales_order_returns');
	}

}
