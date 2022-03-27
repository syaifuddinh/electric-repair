<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTypeTransactionsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('type_transactions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name', 191);
			$table->string('slug', 150)->unique('type_trx_slug_unique');
			$table->boolean('is_saldo')->default(0);
			$table->boolean('is_journal')->default(1);
			$table->boolean('is_penyesuaian')->default(0);
			$table->boolean('is_lock')->default(0);
			$table->date('last_date_lock')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('type_transactions');
	}

}
