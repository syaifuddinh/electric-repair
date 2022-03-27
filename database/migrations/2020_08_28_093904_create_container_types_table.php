<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContainerTypesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('container_types', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('code', 191);
			$table->string('name', 191);
			$table->integer('size');
			$table->string('unit', 15)->default('STD');
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
		Schema::drop('container_types');
	}

}
