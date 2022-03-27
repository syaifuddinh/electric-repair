<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name', 191);
			$table->string('email', 191)->unique();
			$table->string('password', 191);
			$table->string('api_token', 191)->nullable();
			$table->string('remember_token', 100)->nullable();
			$table->timestamps();
			$table->integer('company_id')->unsigned();
			$table->integer('contact_id')->unsigned()->nullable();
			$table->string('username', 191)->unique();
			$table->integer('city_id')->unsigned()->nullable();
			$table->dateTime('last_login')->nullable();
			$table->string('pass_text')->nullable();
			$table->integer('group_id')->unsigned()->nullable();
			$table->boolean('is_admin')->nullable()->default(0);
			$table->string('last_login_ip', 191)->nullable();
			$table->boolean('is_customer')->default(0);
			$table->boolean('is_vendor')->default(0);
			$table->boolean('is_driver')->default(0);
			$table->boolean('is_active')->default(1);
			$table->date('due_date')->nullable();
			$table->string('slug', 30)->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('users');
	}

}
