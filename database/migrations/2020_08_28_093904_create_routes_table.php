<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRoutesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('routes', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('city_from')->unsigned();
			$table->integer('city_to')->unsigned();
			$table->integer('created_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->string('name', 191);
			$table->string('description', 191)->nullable();
			$table->integer('distance')->default(0);
			$table->integer('duration')->default(0);
			$table->integer('type_satuan');
			$table->boolean('is_active')->default(1);
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
		Schema::drop('routes');
	}

}
