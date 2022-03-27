<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCekGirosTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cek_giros', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('bank_id')->unsigned()->nullable();
			$table->integer('penerbit_id')->unsigned()->nullable();
			$table->integer('penerima_id')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('kliring_account_id')->unsigned()->nullable();
			$table->date('date_transaction');
			$table->date('date_effective');
			$table->string('code', 191)->nullable();
			$table->string('giro_no', 191);
			$table->string('reff_no', 191)->nullable();
			$table->integer('type');
			$table->integer('jenis');
			$table->float('amount', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
			$table->boolean('is_kliring')->default(0);
			$table->boolean('is_empty')->default(0);
			$table->boolean('is_used')->default(0);
			$table->boolean('is_saldo')->default(0);
			$table->boolean('is_journal_saldo')->default(0);
			$table->boolean('is_journal_empty')->default(0);
			$table->boolean('is_cancel_kliring')->default(0);
			$table->boolean('is_cancel_empty')->default(0);
			$table->string('cancel_kliring_reason', 191)->nullable();
			$table->string('cancel_empty_reason', 191)->nullable();
			$table->string('cancel_giro_reason', 191)->nullable();
			$table->timestamps();
			$table->integer('account_bank_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cek_giros');
	}

}
