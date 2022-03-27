<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToContainersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('containers', function(Blueprint $table)
		{
			$table->foreign('commodity_id')->references('id')->on('commodities')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('container_type_id')->references('id')->on('container_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('manifest_id')->references('id')->on('manifests')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('vendor_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vessel_id')->references('id')->on('vessels')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('voyage_schedule_id')->references('id')->on('voyage_schedules')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('containers', function(Blueprint $table)
		{
			$table->dropForeign('containers_commodity_id_foreign');
			$table->dropForeign('containers_company_id_foreign');
			$table->dropForeign('containers_container_type_id_foreign');
			$table->dropForeign('containers_customer_id_foreign');
			$table->dropForeign('containers_manifest_id_foreign');
			$table->dropForeign('containers_vendor_id_foreign');
			$table->dropForeign('containers_vessel_id_foreign');
			$table->dropForeign('containers_voyage_schedule_id_foreign');
		});
	}

}
