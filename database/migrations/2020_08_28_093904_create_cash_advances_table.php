<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCashAdvancesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cash_advances', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('realisation_id')->unsigned()->nullable();
			$table->integer('employee_id')->unsigned();
			$table->integer('account_cash_id')->unsigned()->nullable();
			$table->integer('account_advance_id')->unsigned()->nullable();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('cancel_by')->unsigned()->nullable();
			$table->integer('paid_by')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('update_by')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->date('date_transaction');
			$table->date('due_date')->nullable();
			$table->float('total_cash_advance', 10, 0)->default(0);
			$table->float('total_approve', 10, 0)->default(0);
			$table->integer('status')->default(1);
			$table->string('code', 191)->nullable();
			$table->text('description', 65535)->nullable();
			$table->text('cancel_description', 65535)->nullable();
			$table->boolean('is_realisation')->default(0);
			$table->timestamps();
			$table->date('date_opened');
			$table->date('date_closed');
			$table->integer('cash_transaction_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cash_advances');
	}

}
