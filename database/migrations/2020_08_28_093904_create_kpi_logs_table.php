<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateKpiLogsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('kpi_logs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('kpi_status_id')->unsigned();
			$table->integer('job_order_id')->unsigned()->nullable();
			$table->integer('company_id')->unsigned();
			$table->integer('invoice_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->text('description', 65535)->nullable();
			$table->timestamp('date_update')->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->timestamps();
			$table->string('file_name', 191)->nullable();
			$table->string('extension', 191)->nullable();
			$table->integer('manifest_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('kpi_logs');
	}

}
