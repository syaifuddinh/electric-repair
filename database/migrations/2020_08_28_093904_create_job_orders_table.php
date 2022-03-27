<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobOrdersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_orders', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('customer_id')->unsigned();
			$table->integer('route_id')->unsigned()->nullable();
			$table->integer('transaction_type_id')->unsigned()->nullable();
			$table->integer('service_type_id')->unsigned();
			$table->integer('service_id')->unsigned();
			$table->integer('sender_id')->unsigned()->nullable();
			$table->integer('receiver_id')->unsigned()->nullable();
			$table->integer('quotation_id')->unsigned()->nullable();
			$table->integer('quotation_detail_id')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('journal_temp_id')->unsigned()->nullable();
			$table->integer('collectible_id')->unsigned()->nullable();
			$table->integer('work_order_id')->unsigned();
			$table->integer('invoice_id')->unsigned()->nullable();
			$table->integer('receivable_id')->unsigned()->nullable();
			$table->integer('vehicle_type_id')->unsigned()->nullable();
			$table->integer('commodity_id')->unsigned()->nullable();
			$table->integer('kpi_id')->unsigned()->nullable();
			$table->integer('cancel_by')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->dateTime('shipment_date')->nullable();
			$table->dateTime('shipment_done')->nullable();
			$table->float('duration', 10, 0)
            ->nullable(false)
            ->default(1);
            $table->float('price', 10, 0)->default(0);
			$table->float('total_price', 10, 0)->default(0);
			$table->text('description', 65535)->nullable();
			$table->integer('status')->default(1);
			$table->float('total_unit', 20)->default(1.00);
			$table->integer('total_item')->default(0);
			$table->boolean('is_cancel')->default(0);
			$table->date('cancel_date')->nullable();
			$table->text('cancel_description', 65535)->nullable();
			$table->boolean('is_manifest')->default(0);
			$table->boolean('is_done')->default(0);
			$table->string('code_invoice', 191)->nullable();
			$table->date('date_invoice')->nullable();
			$table->string('no_po_customer', 191)->nullable();
			$table->string('no_bl', 191)->nullable();
			$table->timestamps();
			$table->integer('moda_id')->unsigned()->nullable();
			$table->integer('container_type_id')->unsigned()->nullable();
			$table->string('reff_no', 191)->nullable();
			$table->string('docs_no', 191)->nullable();
			$table->string('docs_reff_no', 191)->nullable();
			$table->string('piece_name', 191)->nullable();
			$table->integer('piece_id')->unsigned()->nullable();
			$table->integer('work_order_detail_id')->unsigned()->nullable();
			$table->string('aju_number', 191)->nullable();
			$table->integer('submit')->default(1);
			$table->integer('warehouse_id')->unsigned()->nullable();
			$table->integer('warehouse_staff_id')->unsigned()->nullable();
			$table->integer('imposition')->nullable();
			$table->boolean('is_operational_done')->default(0);
			$table->string('vessel_name', 191)->nullable();
			$table->string('voyage_no', 191)->nullable();
			$table->string('document_name', 191)->nullable();
			$table->integer('is_handling')->default(0);
			$table->integer('is_warehouse')->default(0);
			$table->integer('is_packaging')->default(0);
			$table->integer('is_warehouserent')->default(0);
			$table->integer('is_stuffing')->default(0);
			$table->string('uniqid', 191)->nullable();
			$table->integer('warehouse_receipt_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_orders');
	}

}
