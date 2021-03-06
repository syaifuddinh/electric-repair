<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWorkOrderDraftsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('work_order_drafts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('create_by')->unsigned();
			$table->integer('company_id')->unsigned();
			$table->integer('customer_id')->unsigned();
			$table->string('name', 191)->nullable();
			$table->string('no_bl', 191)->nullable();
			$table->string('aju_number', 191)->nullable();
			$table->text('description', 65535)->nullable();
			$table->date('date')->nullable();
			$table->boolean('is_done')->default(0);
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
		Schema::drop('work_order_drafts');
	}

}
