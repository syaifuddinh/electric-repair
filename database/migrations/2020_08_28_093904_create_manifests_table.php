<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateManifestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('manifests', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('transaction_type_id')->unsigned()->nullable();
			$table->integer('vehicle_type_id')->unsigned()->nullable();
			$table->integer('container_type_id')->unsigned()->nullable();
			$table->integer('route_id')->unsigned()->nullable();
			$table->integer('moda_id')->unsigned()->nullable();
			$table->integer('vehicle_id')->unsigned()->nullable();
			$table->integer('driver_id')->unsigned()->nullable();
			$table->integer('helper_id')->unsigned()->nullable();
			$table->integer('container_id')->unsigned()->nullable();
			$table->integer('delivery_order_id')->unsigned()->nullable();
			$table->integer('cancel_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->dateTime('date_manifest')->nullable();
			$table->string('reff_no', 191)->nullable();
			$table->string('description', 191)->nullable();
			$table->integer('status')->default(1);
			$table->boolean('is_cancel')->default(0);
			$table->date('cancel_date')->nullable();
			$table->string('cancel_description', 191)->nullable();
			$table->integer('status_cost')->default(1);
			$table->boolean('is_container')->default(0);
			$table->string('seal_number', 191)->nullable();
			$table->timestamps();
			$table->boolean('is_full')->nullable()->default(0);
			$table->boolean('is_invoice')->default(0);
			$table->dateTime('etd_time')->nullable();
			$table->dateTime('eta_time')->nullable();
			$table->dateTime('depart')->nullable();
			$table->dateTime('arrive')->nullable();
			$table->string('nopol', 191)->nullable();
			$table->string('driver', 191)->nullable();
			$table->integer('is_internal_driver')->default(1);
			$table->string('container_no', 191)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('manifests');
	}

}
