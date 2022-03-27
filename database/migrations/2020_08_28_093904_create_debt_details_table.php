<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDebtDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('debt_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('payable_id')->unsigned()->nullable();
			$table->integer('type_transaction_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('payable_detail_id')->unsigned()->nullable();
			$table->integer('um_supplier_id')->unsigned()->nullable();
			$table->integer('nota_debet_id')->unsigned()->nullable();
			$table->integer('nota_credit_id')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->float('debt', 10, 0)->default(0);
			$table->float('leftover', 10, 0)->default(0);
			$table->float('total_debt', 10, 0)->default(0);
			$table->text('description', 65535)->nullable();
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
		Schema::drop('debt_details');
	}

}
