<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToVoyageSchedulesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('voyage_schedules', function(Blueprint $table)
		{
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('pod_id')->references('id')->on('ports')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('pol_id')->references('id')->on('ports')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vessel_id')->references('id')->on('vessels')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('voyage_schedules', function(Blueprint $table)
		{
			$table->dropForeign('voyage_schedules_create_by_foreign');
			$table->dropForeign('voyage_schedules_pod_id_foreign');
			$table->dropForeign('voyage_schedules_pol_id_foreign');
			$table->dropForeign('voyage_schedules_vessel_id_foreign');
		});
	}

}
