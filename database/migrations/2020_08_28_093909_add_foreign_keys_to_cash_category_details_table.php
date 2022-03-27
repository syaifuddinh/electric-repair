<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToCashCategoryDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('cash_category_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('cash_categories')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('type_transaction_id')->references('id')->on('type_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('cash_category_details', function(Blueprint $table)
		{
			$table->dropForeign('cash_category_details_header_id_foreign');
			$table->dropForeign('cash_category_details_type_transaction_id_foreign');
		});
	}

}
