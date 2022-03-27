<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNotaDebetsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('nota_debets', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('contact_id')->unsigned();
			$table->integer('payable_id')->unsigned();
			$table->integer('cash_transaction_id')->unsigned()->nullable();
			$table->date('date_transaction');
			$table->integer('jenis');
			$table->string('code', 191)->nullable();
			$table->float('amount', 10, 0)->default(0);
			$table->string('reff_no', 191)->nullable();
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
		Schema::drop('nota_debets');
	}

}
