<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCashCountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cash_counts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->date('date_transaction');
			$table->integer('status')->default(0);
			$table->float('saldo_awal', 10, 0);
			$table->float('bailout', 10, 0);
			$table->string('officer', 191);
			$table->text('description', 65535)->nullable();
			$table->timestamps();
			$table->float('bkk_hari_ini', 10, 0)->default(0);
			$table->float('bkm_hari_ini', 10, 0)->default(0);
			$table->float('saldo_akhir', 10, 0)->default(0);
			$table->float('total_cash_fisik', 10, 0)->default(0);
			$table->float('total_kasbon', 10, 0)->default(0);
			$table->integer('approved_by_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cash_counts');
	}

}
