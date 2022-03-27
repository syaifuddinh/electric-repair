<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateKpiStatusesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('kpi_statuses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('service_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->integer('sort_number');
			$table->string('name', 191);
			$table->float('duration', 10);
			$table->boolean('is_core')->default(0);
			$table->timestamps();
			$table->integer('type')->default(1);
			$table->boolean('is_done')->default(0);
			$table->integer('status')->default(2);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('kpi_statuses');
	}

}
