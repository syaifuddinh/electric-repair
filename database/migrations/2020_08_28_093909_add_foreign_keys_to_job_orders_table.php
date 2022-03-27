<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToJobOrdersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_orders', function(Blueprint $table)
		{
			$table->foreign('cancel_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('collectible_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('commodity_id')->references('id')->on('commodities')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('container_type_id')->references('id')->on('container_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_temp_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('kpi_id')->references('id')->on('kpi_statuses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('moda_id')->references('id')->on('modas')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('piece_id')->references('id')->on('pieces')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('quotation_detail_id')->references('id')->on('quotation_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('quotation_id')->references('id')->on('quotations')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('receivable_id')->references('id')->on('receivables')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('receiver_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('route_id')->references('id')->on('routes')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('sender_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('service_id')->references('id')->on('services')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('service_type_id')->references('id')->on('service_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('transaction_type_id')->references('id')->on('type_transactions')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vehicle_type_id')->references('id')->on('vehicle_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_id')->references('id')->on('warehouses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_receipt_id')->references('id')->on('warehouse_receipts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_staff_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('work_order_detail_id')->references('id')->on('work_order_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('work_order_id')->references('id')->on('work_orders')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_orders', function(Blueprint $table)
		{
			$table->dropForeign('job_orders_cancel_by_foreign');
			$table->dropForeign('job_orders_collectible_id_foreign');
			$table->dropForeign('job_orders_commodity_id_foreign');
			$table->dropForeign('job_orders_company_id_foreign');
			$table->dropForeign('job_orders_container_type_id_foreign');
			$table->dropForeign('job_orders_create_by_foreign');
			$table->dropForeign('job_orders_customer_id_foreign');
			$table->dropForeign('job_orders_journal_id_foreign');
			$table->dropForeign('job_orders_journal_temp_id_foreign');
			$table->dropForeign('job_orders_kpi_id_foreign');
			$table->dropForeign('job_orders_moda_id_foreign');
			$table->dropForeign('job_orders_piece_id_foreign');
			$table->dropForeign('job_orders_quotation_detail_id_foreign');
			$table->dropForeign('job_orders_quotation_id_foreign');
			$table->dropForeign('job_orders_receivable_id_foreign');
			$table->dropForeign('job_orders_receiver_id_foreign');
			$table->dropForeign('job_orders_route_id_foreign');
			$table->dropForeign('job_orders_sender_id_foreign');
			$table->dropForeign('job_orders_service_id_foreign');
			$table->dropForeign('job_orders_service_type_id_foreign');
			$table->dropForeign('job_orders_transaction_type_id_foreign');
			$table->dropForeign('job_orders_vehicle_type_id_foreign');
			$table->dropForeign('job_orders_warehouse_id_foreign');
			$table->dropForeign('job_orders_warehouse_receipt_id_foreign');
			$table->dropForeign('job_orders_warehouse_staff_id_foreign');
			$table->dropForeign('job_orders_work_order_detail_id_foreign');
			$table->dropForeign('job_orders_work_order_id_foreign');
		});
	}

}
