<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateNotificationUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('notification_users', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('notification_id')->unsigned();
			$table->integer('user_id')->unsigned();
			$table->boolean('is_read')->default(0);
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
		Schema::drop('notification_users');
	}

}
