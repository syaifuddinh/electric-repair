<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToInvoiceDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('invoice_details', function(Blueprint $table)
		{
			$table->foreign('cost_type_id')->references('id')->on('cost_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('header_id')->references('id')->on('invoices')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('job_order_detail_id')->references('id')->on('job_order_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('job_order_id')->references('id')->on('job_orders')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('manifest_id')->references('id')->on('manifests')->onUpdate('RESTRICT')->onDelete('SET NULL');
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
		Schema::table('invoice_details', function(Blueprint $table)
		{
			$table->dropForeign('invoice_details_cost_type_id_foreign');
			$table->dropForeign('invoice_details_create_by_foreign');
			$table->dropForeign('invoice_details_header_id_foreign');
			$table->dropForeign('invoice_details_job_order_detail_id_foreign');
			$table->dropForeign('invoice_details_job_order_id_foreign');
			$table->dropForeign('invoice_details_manifest_id_foreign');
			$table->dropForeign('invoice_details_work_order_id_foreign');
		});
	}

}
