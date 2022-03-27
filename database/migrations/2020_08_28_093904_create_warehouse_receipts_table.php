<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWarehouseReceiptsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('warehouse_receipts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('customer_id')->unsigned();
			$table->integer('warehouse_id')->unsigned();
			$table->integer('sender_id')->unsigned()->nullable();
			$table->integer('receiver_id')->unsigned()->nullable();
			$table->integer('collectible_id')->unsigned()->nullable();
			$table->integer('warehouse_staff_id')->unsigned()->nullable();
			$table->integer('type_transaction_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->string('reff_no', 191)->nullable();
			$table->timestamp('receive_date')->default(DB::raw('CURRENT_TIMESTAMP'));
			$table->dateTime('stripping_done')->nullable();
			$table->float('total_qty', 10, 0)->default(0);
			$table->integer('is_export')->unsigned()->nullable()->default(0);
			$table->text('description', 65535)->nullable();
			$table->integer('status')->default(1);
			$table->timestamps();
			$table->integer('work_order_id')->unsigned()->nullable();
			$table->integer('stripping_type')->unsigned()->nullable()->default(1);
			$table->integer('is_overtime')->unsigned()->nullable()->default(0);
			$table->string('nopol', 191)->nullable();
			$table->string('driver', 191)->nullable();
			$table->string('phone_number', 191)->nullable();
			$table->string('city_to', 191)->nullable();
			$table->integer('company_id');
			$table->string('sender', 100)->nullable();
			$table->string('receiver', 100);
			$table->string('ttd', 1000)->nullable();
			$table->text('ttd_driver', 65535)->nullable();
			$table->string('package', 191)->nullable();
			$table->integer('vehicle_type_id')->unsigned()->nullable()->index();
			$table->integer('job_order_pengiriman_id')->unsigned()->nullable()->index();
			$table->softDeletes()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('warehouse_receipts');
	}

}
