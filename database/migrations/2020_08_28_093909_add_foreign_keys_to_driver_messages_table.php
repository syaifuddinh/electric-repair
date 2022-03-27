<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDriverMessagesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('driver_messages', function(Blueprint $table)
		{
			$table->foreign('driver_id')->references('id')->on('contacts')->onUpdate('CASCADE')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('driver_messages', function(Blueprint $table)
		{
			$table->dropForeign('driver_messages_driver_id_foreign');
		});
	}

}
