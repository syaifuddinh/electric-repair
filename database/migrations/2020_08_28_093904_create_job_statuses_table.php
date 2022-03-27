<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobStatusesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_statuses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('urut');
			$table->string('name', 191);
			$table->boolean('is_start')->default(0);
			$table->boolean('is_finish')->default(0);
			$table->boolean('is_repeat')->default(0);
			$table->boolean('is_cancel')->default(0);
			$table->boolean('is_reject')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_statuses');
	}

}
