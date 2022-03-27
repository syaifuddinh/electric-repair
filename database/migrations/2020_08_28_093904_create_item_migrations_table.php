<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateItemMigrationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('item_migrations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_from_id')->unsigned();
			$table->integer('rack_from_id')->unsigned()->nullable();
			$table->integer('warehouse_to_id')->unsigned();
			$table->integer('rack_to_id')->unsigned()->nullable();
			$table->date('date_transaction');
			$table->date('date_approve')->nullable();
			$table->date('date_cancel')->nullable();
			$table->string('code', 191)->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('cancel_by')->unsigned()->nullable();
			$table->integer('status')->default(1);
			$table->text('description', 65535)->nullable();
			$table->text('cancel_reason', 65535)->nullable();
			$table->timestamps();
			$table->string('no_surat_jalan', 100)->nullable();
			$table->integer('is_inventory')->default(0);
			$table->integer('warehouse_receipt_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('item_migrations');
	}

}
