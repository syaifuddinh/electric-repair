<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDashboardDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('dashboard_details', function(Blueprint $table)
		{
			$table->foreign('header_id')->references('id')->on('dashboards')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('widget_id')->references('id')->on('widgets')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('dashboard_details', function(Blueprint $table)
		{
			$table->dropForeign('dashboard_details_header_id_foreign');
			$table->dropForeign('dashboard_details_widget_id_foreign');
		});
	}

}
