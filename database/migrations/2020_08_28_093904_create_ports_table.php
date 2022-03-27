<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePortsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('ports', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('code', 191);
			$table->string('name', 191);
			$table->string('island_name', 191);
			$table->string('latitude', 191)->nullable();
			$table->string('longitude', 191)->nullable();
			$table->timestamps();
			$table->integer('country_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('ports');
	}

}
