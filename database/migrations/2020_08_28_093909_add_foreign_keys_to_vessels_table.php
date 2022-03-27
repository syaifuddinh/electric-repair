<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToVesselsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('vessels', function(Blueprint $table)
		{
			$table->foreign('vendor_id', 'vessel_vendor_id_for')->references('id')->on('contacts')->onUpdate('CASCADE')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('vessels', function(Blueprint $table)
		{
			$table->dropForeign('vessel_vendor_id_for');
		});
	}

}
