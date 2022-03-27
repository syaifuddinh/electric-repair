<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobOrderDocumentsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_order_documents', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->string('name', 191);
			$table->text('description', 65535)->nullable();
			$table->string('file_name', 191);
			$table->string('extension', 191);
			$table->date('upload_date');
			$table->timestamps();
			$table->boolean('is_customer_view')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_order_documents');
	}

}
