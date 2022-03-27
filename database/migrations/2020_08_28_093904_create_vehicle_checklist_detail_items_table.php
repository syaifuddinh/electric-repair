<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVehicleChecklistDetailItemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vehicle_checklist_detail_items', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('vehicle_checklist_id')->unsigned();
			$table->boolean('is_exist');
			$table->boolean('is_function');
			$table->integer('condition');
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
		Schema::drop('vehicle_checklist_detail_items');
	}

}
