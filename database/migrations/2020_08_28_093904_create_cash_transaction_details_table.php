<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCashTransactionDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cash_transaction_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('account_id')->unsigned()->nullable();
			$table->integer('cash_category_id')->unsigned()->nullable();
			$table->integer('contact_id')->unsigned()->nullable();
			$table->float('amount', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
			$table->timestamps();
			$table->integer('jenis')->default(1);
			$table->string('uploaded_file', 191)->nullable();
			$table->integer('job_order_cost_id')->unsigned()->nullable()->index();
			$table->integer('manifest_cost_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cash_transaction_details');
	}

}
