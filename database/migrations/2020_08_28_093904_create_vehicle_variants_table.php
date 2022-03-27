<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVehicleVariantsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vehicle_variants', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('vehicle_type_id')->unsigned();
			$table->integer('vehicle_manufacturer_id')->unsigned();
			$table->integer('bbm_type_id')->unsigned();
			$table->integer('vehicle_joint_id')->unsigned();
			$table->integer('tire_size_id')->unsigned();
			$table->integer('transmission')->unsigned();
			$table->string('code', 191);
			$table->string('name', 191);
			$table->integer('year_manufacture');
			$table->integer('cylinder');
			$table->integer('cc_capacity');
			$table->integer('bbm_capacity');
			$table->integer('joints');
			$table->integer('seat');
			$table->integer('first_km_initial');
			$table->integer('next_km_initial');
			$table->float('cost', 10, 0);
			$table->string('description', 191)->nullable();
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
		Schema::drop('vehicle_variants');
	}

}
