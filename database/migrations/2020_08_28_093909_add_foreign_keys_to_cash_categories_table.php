<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCashCategoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cash_categories', function(Blueprint $table)
		{
			$table->foreign('parent_id')->references('id')->on('cash_categories')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cash_categories', function(Blueprint $table)
		{
			$table->dropForeign('cash_categories_parent_id_foreign');
		});
	}

}
