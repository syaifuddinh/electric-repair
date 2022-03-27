<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToJobOrderDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('job_order_details', function(Blueprint $table)
		{
			$table->foreign('commodity_id')->references('id')->on('commodities')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('job_orders')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('manifest_id')->references('id')->on('manifests')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('piece_id')->references('id')->on('pieces')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('price_list_id')->references('id')->on('price_lists')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('quotation_detail_id')->references('id')->on('quotation_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('quotation_id')->references('id')->on('quotations')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('receiver_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('sender_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('warehouse_receipt_id')->references('id')->on('warehouse_receipts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('job_order_details', function(Blueprint $table)
		{
			$table->dropForeign('job_order_details_commodity_id_foreign');
			$table->dropForeign('job_order_details_create_by_foreign');
			$table->dropForeign('job_order_details_header_id_foreign');
			$table->dropForeign('job_order_details_manifest_id_foreign');
			$table->dropForeign('job_order_details_piece_id_foreign');
			$table->dropForeign('job_order_details_price_list_id_foreign');
			$table->dropForeign('job_order_details_quotation_detail_id_foreign');
			$table->dropForeign('job_order_details_quotation_id_foreign');
			$table->dropForeign('job_order_details_receiver_id_foreign');
			$table->dropForeign('job_order_details_sender_id_foreign');
			$table->dropForeign('job_order_details_warehouse_receipt_detail_id_foreign');
			$table->dropForeign('job_order_details_warehouse_receipt_id_foreign');
		});
	}

}
