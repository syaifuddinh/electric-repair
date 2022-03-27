<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePurchaseRequestsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('purchase_requests', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('warehouse_id')->unsigned()->nullable();
			$table->integer('supplier_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('reject_by')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->date('date_request');
			$table->date('date_needed');
			$table->date('date_approved')->nullable();
			$table->date('date_reject')->nullable();
			$table->string('reject_reason', 191)->nullable();
			$table->string('description', 191)->nullable();
			$table->integer('status')->default(1);
			$table->timestamps();
			$table->integer('vehicle_maintenance_id')->unsigned()->nullable();
			$table->boolean('is_pallet')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('purchase_requests');
	}

}
