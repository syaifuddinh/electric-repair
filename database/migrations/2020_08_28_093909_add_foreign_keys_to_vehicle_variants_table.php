<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToVehicleVariantsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vehicle_variants', function(Blueprint $table)
		{
			$table->foreign('bbm_type_id')->references('id')->on('bbm_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('tire_size_id')->references('id')->on('tire_sizes')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_joint_id')->references('id')->on('vehicle_joints')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_manufacturer_id')->references('id')->on('vehicle_manufacturers')->onUpdate('RESTRICT')->onDelete('RESTRICT');
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
		Schema::table('vehicle_variants', function(Blueprint $table)
		{
			$table->dropForeign('vehicle_variants_bbm_type_id_foreign');
			$table->dropForeign('vehicle_variants_tire_size_id_foreign');
			$table->dropForeign('vehicle_variants_vehicle_joint_id_foreign');
			$table->dropForeign('vehicle_variants_vehicle_manufacturer_id_foreign');
			$table->dropForeign('vehicle_variants_vehicle_type_id_foreign');
		});
	}

}
