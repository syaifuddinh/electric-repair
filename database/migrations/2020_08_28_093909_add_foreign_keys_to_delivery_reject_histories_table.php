<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDeliveryRejectHistoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('delivery_reject_histories', function(Blueprint $table)
		{
			$table->foreign('delivery_order_id')->references('id')->on('delivery_order_drivers')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('driver_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('delivery_reject_histories', function(Blueprint $table)
		{
			$table->dropForeign('delivery_reject_histories_delivery_order_id_foreign');
			$table->dropForeign('delivery_reject_histories_driver_id_foreign');
		});
	}

}
