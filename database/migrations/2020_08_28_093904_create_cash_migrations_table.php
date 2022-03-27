<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCashMigrationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cash_migrations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('code', 191)->nullable();
			$table->integer('company_from')->unsigned();
			$table->integer('company_to')->unsigned();
			$table->date('date_request');
			$table->date('date_needed');
			$table->date('date_realisation')->nullable();
			$table->integer('create_by')->unsigned()->nullable();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('approve_direction_by')->unsigned()->nullable();
			$table->integer('realisation_by')->unsigned()->nullable();
			$table->integer('cash_account_from')->unsigned();
			$table->integer('cash_account_to')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->float('total', 20)->default(0.00);
			$table->text('description', 65535)->nullable();
			$table->integer('status')->default(1);
			$table->timestamps();
			$table->text('reject_reason', 65535);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('cash_migrations');
	}

}
