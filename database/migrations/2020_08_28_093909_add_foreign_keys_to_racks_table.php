<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToRacksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('racks', function(Blueprint $table)
		{
			$table->foreign('storage_type_id')->references('id')->on('storage_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('racks', function(Blueprint $table)
		{
			$table->dropForeign('racks_storage_type_id_foreign');
			$table->dropForeign('racks_warehouse_id_foreign');
		});
	}

}
