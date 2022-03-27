<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateShipmentStatusesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('shipment_statuses', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('warehouse_receipt_id')->unsigned()->index();
			$table->integer('status')->unsigned()->default(0)->index();
			$table->date('status_date')->index();
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
		Schema::drop('shipment_statuses');
	}

}
