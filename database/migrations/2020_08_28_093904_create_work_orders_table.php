<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWorkOrdersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('work_orders', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('customer_id')->unsigned();
			$table->integer('quotation_id')->unsigned()->nullable();
			$table->integer('updated_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->integer('total_job_order')->default(0);
			$table->integer('status')->default(1);
			$table->timestamps();
			$table->integer('quotation_detail_id')->unsigned()->nullable();
			$table->integer('price_list_id')->unsigned()->nullable();
			$table->string('name', 191)->nullable();
			$table->date('date')->nullable();
			$table->integer('company_id')->unsigned()->nullable();
			$table->float('qty', 20)->default(0.00);
			$table->boolean('is_invoice')->default(0);
			$table->string('no_bl', 191)->nullable();
			$table->string('aju_number', 191)->nullable();
			$table->integer('is_job_packet')->default(0)->index();
			$table->integer('invoice_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('work_orders');
	}

}
