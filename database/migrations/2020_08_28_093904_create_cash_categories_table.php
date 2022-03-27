<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCashCategoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('cash_categories', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('code', 191);
			$table->string('name', 191);
			$table->integer('kategori');
			$table->integer('jenis');
			$table->boolean('is_base')->default(0);
			$table->integer('parent_id')->unsigned()->nullable();
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
		Schema::drop('cash_categories');
	}

}
