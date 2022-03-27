<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateServicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('services', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name', 191);
			$table->string('description', 191)->nullable();
			$table->boolean('is_default')->default(0);
			$table->timestamps();
			$table->integer('service_type_id')->unsigned();
			$table->integer('account_sale_id')->unsigned()->nullable();
			$table->integer('service_group_id')->unsigned()->nullable();
			$table->integer('is_warehouse')->default(0);
			$table->integer('is_wh_rent')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('services');
	}

}
