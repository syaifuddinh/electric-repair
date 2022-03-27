<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToJobOrderCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_order_costs', function(Blueprint $table)
		{
			$table->foreign('manifest_cost_id')->references('id')->on('manifest_costs')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_order_costs', function(Blueprint $table)
		{
			$table->dropForeign('job_order_costs_manifest_cost_id_foreign');
		});
	}

}
