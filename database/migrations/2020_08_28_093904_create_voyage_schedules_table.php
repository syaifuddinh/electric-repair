<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVoyageSchedulesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('voyage_schedules', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('vessel_id')->unsigned();
			$table->integer('pol_id')->unsigned();
			$table->integer('pod_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->string('voyage', 191);
			$table->integer('total_container');
			$table->timestamp('etd')->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->dateTime('eta')->default('0000-00-00 00:00:00');
			$table->dateTime('departure')->nullable();
			$table->dateTime('arrival')->nullable();
			$table->timestamps();
			$table->integer('countries_id')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('voyage_schedules');
	}

}
