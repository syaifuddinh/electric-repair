<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInqueryActivitiesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('inquery_activities', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('sales_id')->unsigned()->nullable();
			$table->integer('customer_stage_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->date('date_activity')->nullable();
			$table->boolean('is_done')->default(0);
			$table->boolean('is_opportunity')->default(1);
			$table->date('date_done')->nullable();
			$table->text('description', 65535)->nullable();
			$table->string('file_name', 191)->nullable();
			$table->string('extension', 191)->nullable();
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
		Schema::drop('inquery_activities');
	}

}
