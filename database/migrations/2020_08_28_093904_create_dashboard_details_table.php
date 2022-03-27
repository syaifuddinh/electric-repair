<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDashboardDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dashboard_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned()->index();
			$table->integer('widget_id')->unsigned()->index();
			$table->integer('row')->default(0)->index();
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
		Schema::drop('dashboard_details');
	}

}
