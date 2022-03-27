<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRetursTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('returs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('receipt_list_id')->unsigned();
			$table->integer('company_id')->unsigned();
			$table->integer('supplier_id')->unsigned();
			$table->integer('warehouse_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('account_cash_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('type_retur');
			$table->string('code', 191)->nullable();
			$table->date('date_transaction');
			$table->string('description', 191)->nullable();
			$table->integer('status');
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
		Schema::drop('returs');
	}

}
