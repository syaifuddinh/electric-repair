<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobOrderDetailOutputsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_order_detail_outputs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('job_order_detail_id')->unsigned();
			$table->date('date_out');
			$table->float('qty', 10, 0)->default(0);
			$table->float('weight', 10, 0)->default(0);
			$table->float('volume', 10, 0)->default(0);
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
		Schema::drop('job_order_detail_outputs');
	}

}
