<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWorkOrderDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('work_order_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned()->nullable();
			$table->integer('quotation_detail_id')->unsigned()->nullable();
			$table->integer('price_list_id')->unsigned()->nullable();
			$table->integer('total_job_order')->default(0);
			$table->timestamps();
			$table->boolean('is_done')->default(0);
			$table->text('description', 65535)->nullable();
			$table->float('qty', 20, 0)->default(0);
			$table->float('qty_leftover', 20, 0)->default(0);
			$table->integer('customer_price_id');
			$table->float('price_full', 10, 0);
			$table->string('service_name', 100);
			$table->string('service_type_name', 100);
			$table->string('piece_name', 100)->nullable();
			$table->string('container_type_name', 100)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('work_order_details');
	}

}
