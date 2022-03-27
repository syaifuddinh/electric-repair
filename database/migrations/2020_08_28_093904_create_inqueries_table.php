<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInqueriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('inqueries', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('customer_id')->unsigned();
			$table->integer('sales_opportunity_id')->unsigned()->nullable();
			$table->integer('sales_inquery_id')->unsigned()->nullable();
			$table->integer('quotation_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('customer_stage_id')->unsigned()->nullable();
			$table->integer('inquery_by')->unsigned()->nullable();
			$table->integer('opportunity_id')->unsigned()->nullable();
			$table->string('code_inquery', 191)->nullable();
			$table->string('code_opportunity', 191)->nullable();
			$table->date('date_inquery')->nullable();
			$table->date('date_opportunity')->nullable();
			$table->date('date_quotation')->nullable();
			$table->integer('status')->default(1);
			$table->text('description_inquery', 65535)->nullable();
			$table->text('description_opportunity', 65535)->nullable();
			$table->integer('type_send')->nullable();
			$table->timestamps();
			$table->integer('cancel_opportunity_by')->unsigned()->nullable();
			$table->dateTime('cancel_opportunity_date')->nullable();
			$table->integer('cancel_inquery_by')->unsigned()->nullable();
			$table->dateTime('cancel_inquery_date')->nullable();
			$table->string('interest', 191)->nullable();
			$table->integer('lead_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('inqueries');
	}

}
