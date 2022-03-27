<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUmCustomerDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('um_customer_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('type_transaction_id')->unsigned();
			$table->integer('relation_id')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->date('date_transaction');
			$table->float('debet', 10, 0)->default(0);
			$table->float('credit', 10, 0)->default(0);
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
		Schema::drop('um_customer_details');
	}

}
