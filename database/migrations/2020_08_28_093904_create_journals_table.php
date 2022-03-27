<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJournalsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('journals', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('type_transaction_id')->unsigned();
			$table->integer('created_by')->unsigned();
			$table->integer('posting_by')->unsigned()->nullable();
			$table->integer('unposting_by')->unsigned()->nullable();
			$table->integer('relation_id')->unsigned()->nullable();
			$table->date('date_transaction');
			$table->date('date_posting')->nullable();
			$table->date('date_unposting')->nullable();
			$table->string('code', 191)->nullable();
			$table->string('description', 191)->nullable();
			$table->string('unposting_reason', 191)->nullable();
			$table->float('debet', 10, 0)->default(0);
			$table->float('credit', 10, 0)->default(0);
			$table->integer('status')->default(1)->comment('1 = Draft ; 2 = Pengajuan ; 3 = Sudah Diposting');
			$table->timestamps();
			$table->integer('source')->nullable()->comment('1=jurnal umum, 2=transaksi favorit');
			$table->integer('is_audit')->default(0)->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('journals');
	}

}
