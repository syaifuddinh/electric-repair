<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePayablesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('payables', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('contact_id')->unsigned();
			$table->integer('type_transaction_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('relation_id')->unsigned()->nullable();
			$table->integer('created_by')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->date('date_transaction');
			$table->date('date_tempo')->nullable();
			$table->string('description', 191)->nullable();
			$table->float('debet', 10, 0)->default(0);
			$table->float('credit', 10, 0)->default(0);
			$table->timestamps();
			$table->boolean('is_invoice')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('payables');
	}

}
