<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToUsingItemsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('using_items', function(Blueprint $table)
		{
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_id')->references('id')->on('vehicles')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_maintenance_id')->references('id')->on('vehicle_maintenances')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('using_items', function(Blueprint $table)
		{
			$table->dropForeign('using_items_company_id_foreign');
			$table->dropForeign('using_items_vehicle_id_foreign');
			$table->dropForeign('using_items_vehicle_maintenance_id_foreign');
		});
	}

}
