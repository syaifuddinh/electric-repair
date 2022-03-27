<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCategoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('categories', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('parent_id')->unsigned()->nullable();
			$table->string('code', 191);
			$table->string('name', 191);
			$table->boolean('is_tire')->default(0);
			$table->boolean('is_asset')->default(0);
			$table->boolean('is_jasa')->default(0);
			$table->boolean('is_ban_luar')->default(0);
			$table->boolean('is_ban_dalam')->default(0);
			$table->boolean('is_marset')->default(0);
			$table->string('description', 191)->nullable();
			$table->boolean('ban_master')->default(0);
			$table->timestamps();
			$table->boolean('is_pallet')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('categories');
	}

}
