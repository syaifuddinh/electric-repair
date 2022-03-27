<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToManifestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('manifests', function(Blueprint $table)
		{
			$table->foreign('cancel_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('container_id')->references('id')->on('containers')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('container_type_id')->references('id')->on('container_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('driver_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('helper_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('moda_id')->references('id')->on('modas')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('route_id')->references('id')->on('routes')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('transaction_type_id')->references('id')->on('type_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_id')->references('id')->on('vehicles')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('manifests', function(Blueprint $table)
		{
			$table->dropForeign('manifests_cancel_id_foreign');
			$table->dropForeign('manifests_company_id_foreign');
			$table->dropForeign('manifests_container_id_foreign');
			$table->dropForeign('manifests_container_type_id_foreign');
			$table->dropForeign('manifests_create_by_foreign');
			$table->dropForeign('manifests_driver_id_foreign');
			$table->dropForeign('manifests_helper_id_foreign');
			$table->dropForeign('manifests_moda_id_foreign');
			$table->dropForeign('manifests_route_id_foreign');
			$table->dropForeign('manifests_transaction_type_id_foreign');
			$table->dropForeign('manifests_vehicle_id_foreign');
			$table->dropForeign('manifests_vehicle_type_id_foreign');
		});
	}

}
