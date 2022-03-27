<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateCustomerStagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('customer_stages', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name', 191);
			$table->integer('bobot');
			$table->timestamps();
			$table->boolean('is_close_deal')->default(0);
			$table->boolean('is_prospect')->default(0);
			$table->boolean('is_negotiation')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('customer_stages');
	}

}
