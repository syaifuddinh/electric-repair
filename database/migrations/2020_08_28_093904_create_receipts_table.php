<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReceiptsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('receipts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('po_id')->unsigned()->nullable();
			$table->integer('po_return_id')->unsigned()->nullable();
			$table->integer('sales_return_id')->unsigned()->nullable();
			$table->integer('usage_return_id')->unsigned()->nullable();
			$table->integer('item_migration_id')->unsigned()->nullable();
			$table->integer('relation_id')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->integer('status')->default(1);
			$table->boolean('is_tms')->default(0);
			$table->timestamps();
			$table->string('relation_code', 191)->nullable();
			$table->integer('relation_type')->default(1);
			$table->integer('account_item_id')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->float('price', 10, 0)->default(0);
			$table->string('gift_from', 200)->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('receipts');
	}

}
