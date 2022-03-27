<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobPacketsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_packets', function(Blueprint $table)
		{
			$table->bigInteger('id', true)->unsigned();
			$table->integer('job_order_id')->unsigned()->index();
			$table->integer('work_order_detail_id')->unsigned()->index();
			$table->integer('duration')->default(1)->index();
			$table->integer('qty')->default(1)->index();
			$table->integer('price')->default(0)->index();
			$table->integer('total_price')->default(0)->index();
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
		Schema::drop('job_packets');
	}

}
