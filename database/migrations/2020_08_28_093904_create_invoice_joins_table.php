<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInvoiceJoinsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('invoice_joins', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('serial')->unsigned();
			$table->integer('invoice_id')->unsigned();
			$table->integer('type_wo');
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
		Schema::drop('invoice_joins');
	}

}
