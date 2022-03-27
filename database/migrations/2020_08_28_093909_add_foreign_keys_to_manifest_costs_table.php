<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToManifestCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('manifest_costs', function(Blueprint $table)
		{
			$table->foreign('job_order_cost_id')->references('id')->on('job_order_costs')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('manifest_costs', function(Blueprint $table)
		{
			$table->dropForeign('manifest_costs_job_order_cost_id_foreign');
		});
	}

}
