<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCompaniesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('companies', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('area_id')->unsigned();
			$table->integer('city_id')->unsigned();
			$table->string('code', 191);
			$table->string('name', 191);
			$table->string('address', 191);
			$table->string('phone', 191)->nullable();
			$table->string('email', 191)->nullable();
			$table->string('website', 191)->nullable();
			$table->boolean('is_pusat')->default(0);
			$table->timestamps();
			$table->string('rek_no_1')->nullable();
			$table->string('rek_name_1')->nullable();
			$table->string('rek_bank_1')->nullable();
			$table->string('rek_no_2')->nullable();
			$table->string('rek_name_2')->nullable();
			$table->string('rek_bank_2')->nullable();
			$table->float('plafond', 10, 0)->nullable();
			$table->integer('cash_account_id')->unsigned()->nullable();
			$table->integer('bank_account_id')->unsigned()->nullable();
			$table->integer('mutation_account_id')->unsigned()->nullable();
			$table->integer('account_kasbon_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('companies');
	}

}
