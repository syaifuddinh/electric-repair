<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInqueryCustomersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('inquery_customers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('customer_id')->unsigned();
			$table->string('code', 191)->nullable();
			$table->string('name', 191);
			$table->text('description', 65535)->nullable();
			$table->string('file_name', 191)->nullable();
			$table->string('file_extension', 191)->nullable();
			$table->boolean('is_done')->default(0);
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
		Schema::drop('inquery_customers');
	}

}
