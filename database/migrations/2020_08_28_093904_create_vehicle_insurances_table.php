<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVehicleInsurancesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vehicle_insurances', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('insurance_id')->unsigned();
			$table->integer('account_id')->unsigned()->nullable();
			$table->integer('vehicle_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('type');
			$table->integer('payment');
			$table->integer('termin')->default(0);
			$table->float('premi', 10, 0);
			$table->string('polis_no', 191);
			$table->string('tjh', 191);
			$table->date('start_date');
			$table->date('end_date');
			$table->date('date_credit');
			$table->boolean('is_active');
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
		Schema::drop('vehicle_insurances');
	}

}
