<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsingItemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('using_items', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('vehicle_id')->unsigned()->nullable()->index();
			$table->integer('vehicle_maintenance_id')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->date('date_request');
			$table->date('date_approve');
			$table->date('date_pemakaian')->nullable();
			$table->integer('status')->default(1);
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
		Schema::drop('using_items');
	}

}
