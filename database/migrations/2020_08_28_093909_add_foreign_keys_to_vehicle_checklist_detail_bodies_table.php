<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToVehicleChecklistDetailBodiesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vehicle_checklist_detail_bodies', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('vehicle_checklist_items')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('vehicle_body_id')->references('id')->on('vehicle_bodies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vehicle_checklist_detail_bodies', function(Blueprint $table)
		{
			$table->dropForeign('vehicle_checklist_detail_bodies_header_id_foreign');
			$table->dropForeign('vehicle_checklist_detail_bodies_vehicle_body_id_foreign');
		});
	}

}
