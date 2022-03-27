<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDeliveryRejectHistoriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('delivery_reject_histories', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('delivery_order_id')->unsigned();
			$table->integer('driver_id')->unsigned();
			$table->text('description', 65535)->nullable();
			$table->string('lat', 191)->nullable();
			$table->string('lng', 191)->nullable();
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('delivery_reject_histories');
	}

}
