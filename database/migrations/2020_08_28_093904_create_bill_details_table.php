<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBillDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('bill_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('receivable_id')->unsigned()->nullable();
			$table->integer('type_transaction_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->float('bill', 10, 0)->default(0);
			$table->float('leftover', 10, 0)->default(0);
			$table->float('total_bill', 10, 0)->default(0);
			$table->text('description', 65535)->nullable();
			$table->timestamps();
			$table->integer('receivable_detail_id')->unsigned()->nullable();
			$table->integer('um_customer_id')->unsigned()->nullable();
			$table->integer('nota_credit_id')->unsigned()->nullable();
			$table->integer('nota_debet_id')->unsigned()->nullable();
			$table->integer('account_id')->unsigned()->nullable();
			$table->integer('jenis')->default(1);
			$table->string('pi_lampiran', 191);
			$table->string('pi_kapal', 191);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('bill_details');
	}

}
