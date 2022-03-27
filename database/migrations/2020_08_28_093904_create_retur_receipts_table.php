<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateReturReceiptsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('retur_receipts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('warehouse_id')->unsigned();
			$table->integer('company_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->date('date_receipt');
			$table->string('receiver', 191);
			$table->string('deliver_no', 191);
			$table->string('description', 191)->nullable();
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
		Schema::drop('retur_receipts');
	}

}
