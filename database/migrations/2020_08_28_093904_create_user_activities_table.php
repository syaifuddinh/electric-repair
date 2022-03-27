<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserActivitiesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('user_activities', function(Blueprint $table)
		{
			$table->bigInteger('id', true)->unsigned();
			$table->string('user_id', 191)->nullable();
			$table->string('relation_id', 191)->nullable();
			$table->string('username', 191)->nullable();
			$table->string('activity', 191);
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
		Schema::drop('user_activities');
	}

}
