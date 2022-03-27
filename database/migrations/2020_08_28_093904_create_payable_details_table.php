<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePayableDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('payable_details', function(Blueprint $table)
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
			$table->boolean('is_journal')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('payable_details');
	}

}
