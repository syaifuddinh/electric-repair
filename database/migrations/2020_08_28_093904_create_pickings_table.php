<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePickingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('pickings', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('code', 200);
			$table->integer('warehouse_id');
			$table->date('date_transaction');
			$table->date('date_approve');
			$table->date('date_cancel');
			$table->integer('create_by');
			$table->integer('approve_by');
			$table->integer('status')->default(1);
			$table->text('description', 65535)->nullable();
			$table->timestamps();
			$table->integer('company_id');
			$table->integer('customer_id');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('pickings');
	}

}
