<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBillsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('bills', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('customer_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('cancel_by')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->date('date_request');
			$table->date('date_cancel')->nullable();
			$table->text('cancel_description', 65535)->nullable();
			$table->text('description', 65535)->nullable();
			$table->integer('status')->default(1);
			$table->string('code_receive', 191)->nullable();
			$table->date('date_receive')->nullable();
			$table->text('description_receive', 65535)->nullable();
			$table->float('paid', 10, 0)->default(0);
			$table->float('overpayment', 10, 0)->default(0);
			$table->timestamps();
			$table->float('total', 10, 0)->default(0);
			$table->date('pi_due_date');
			$table->integer('pi_print_count')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('bills');
	}

}
