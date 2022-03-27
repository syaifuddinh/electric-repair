<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVehicleMaintenancesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vehicle_maintenances', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('vehicle_id')->unsigned();
			$table->integer('vendor_id')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->string('name', 191);
			$table->float('km_rencana', 10, 0)->default(0);
			$table->float('cost_rencana', 10, 0)->default(0);
			$table->date('date_pengajuan');
			$table->date('date_rencana');
			$table->date('date_perawatan')->nullable();
			$table->float('km_realisasi', 10, 0)->default(0);
			$table->float('cost_realisasi', 10, 0)->default(0);
			$table->date('date_realisasi')->nullable();
			$table->integer('status')->default(2);
			$table->string('description', 191)->nullable();
			$table->boolean('is_internal')->default(1);
			$table->timestamps();
			$table->integer('warehouse_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('vehicle_maintenances');
	}

}
