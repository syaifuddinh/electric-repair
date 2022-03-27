<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToVehicleInsurancesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vehicle_insurances', function(Blueprint $table)
		{
			$table->foreign('account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('insurance_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('vehicle_id')->references('id')->on('vehicles')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vehicle_insurances', function(Blueprint $table)
		{
			$table->dropForeign('vehicle_insurances_account_id_foreign');
			$table->dropForeign('vehicle_insurances_insurance_id_foreign');
			$table->dropForeign('vehicle_insurances_journal_id_foreign');
			$table->dropForeign('vehicle_insurances_vehicle_id_foreign');
		});
	}

}
