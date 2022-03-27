<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToRouteCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('route_costs', function(Blueprint $table)
		{
			$table->foreign('commodity_id')->references('id')->on('commodities')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('container_type_id')->references('id')->on('container_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('route_id')->references('id')->on('routes')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('route_costs', function(Blueprint $table)
		{
			$table->dropForeign('route_costs_commodity_id_foreign');
			$table->dropForeign('route_costs_container_type_id_foreign');
			$table->dropForeign('route_costs_created_by_foreign');
			$table->dropForeign('route_costs_route_id_foreign');
		});
	}

}
