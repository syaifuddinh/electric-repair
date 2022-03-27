<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCashTransactionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cash_transactions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('type_transaction_id')->unsigned();
			$table->integer('account_id')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('relation_id')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->date('date_transaction');
			$table->integer('jenis');
			$table->integer('type');
			$table->string('reff', 191)->nullable();
			$table->string('description', 191)->nullable();
			$table->float('total', 10, 0)->default(0);
			$table->timestamps();
			$table->boolean('is_cut')->nullable()->default(0);
			$table->integer('status')->default(1)->comment('1. Belum Persetujuan, 2.  Disetujui, 3. Selesai, 4. DItolak');
			$table->integer('edit_count')->default(0);
			$table->integer('created_by')->unsigned()->nullable();
			$table->integer('parent_id')->unsigned()->nullable();
			$table->integer('status_cost')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cash_transactions');
	}

}
