<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInvoiceDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('invoice_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('job_order_id')->unsigned()->nullable();
			$table->integer('job_order_detail_id')->unsigned()->nullable();
			$table->integer('work_order_id')->unsigned()->nullable();
			$table->integer('cost_type_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->float('price', 10, 0)->default(0);
			$table->float('total_price', 10, 0)->default(0);
			$table->integer('imposition')->nullable();
			$table->float('qty', 10, 0)->default(0);
			$table->text('description', 65535)->nullable();
			$table->boolean('is_other_cost')->default(0);
			$table->integer('type_other_cost')->default(1);
			$table->timestamps();
			$table->integer('manifest_id')->unsigned()->nullable();
			$table->string('imposition_name', 191)->nullable();
			$table->string('commodity_name', 191)->nullable();
			$table->float('discount', 10, 0)->default(0);
			$table->boolean('is_ppn')->default(0);
			$table->float('ppn', 10, 0)->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('invoice_details');
	}

}
