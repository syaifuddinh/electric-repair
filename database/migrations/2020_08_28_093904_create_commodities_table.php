<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCommoditiesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('commodities', function(Blueprint $table)
		{
			$table->increments('id');
			$table->text('name', 65535);
			$table->timestamps();
			$table->boolean('is_default')->default(0);
			$table->boolean('is_expired')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('commodities');
	}

}
