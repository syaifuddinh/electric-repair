<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAccountsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('accounts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('code', 191);
			$table->string('name', 191);
			$table->string('description', 191)->nullable();
			$table->integer('deep')->default(1);
			$table->integer('parent_id')->unsigned()->nullable();
			$table->boolean('is_base')->default(0);
			$table->integer('jenis')->nullable()->default(1);
			$table->integer('group_report')->default(1);
			$table->integer('type_id')->unsigned()->nullable();
			$table->timestamps();
			$table->integer('no_cash_bank')->nullable()->default(0);
			$table->boolean('is_freeze')->default(0);
			$table->integer('company_id')->unsigned()->nullable();
			$table->boolean('is_usd')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('accounts');
	}

}
