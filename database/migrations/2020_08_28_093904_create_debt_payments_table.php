<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDebtPaymentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('debt_payments', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('cash_account_id')->unsigned()->nullable();
			$table->integer('cek_giro_id')->unsigned()->nullable();
			$table->integer('payment_type')->default(1);
			$table->string('reff', 191)->nullable();
			$table->float('total', 10, 0)->default(0);
			$table->text('description', 65535)->nullable();
			$table->timestamps();
			$table->string('filename', 100);
			$table->integer('valid')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('debt_payments');
	}

}
