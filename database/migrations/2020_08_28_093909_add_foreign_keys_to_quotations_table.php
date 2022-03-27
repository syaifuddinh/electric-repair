<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToQuotationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('quotations', function(Blueprint $table)
		{
			$table->foreign('approve_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('approve_direction_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('approve_manager_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cancel_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cancel_quotation_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('contract_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('created_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_stage_id')->references('id')->on('customer_stages')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('lead_id')->references('id')->on('leads')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('parent_id')->references('id')->on('quotations')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('piece_id')->references('id')->on('pieces')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('sales_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('stop_contract_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('submit_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('quotations', function(Blueprint $table)
		{
			$table->dropForeign('quotations_approve_by_foreign');
			$table->dropForeign('quotations_approve_direction_by_foreign');
			$table->dropForeign('quotations_approve_manager_by_foreign');
			$table->dropForeign('quotations_cancel_by_foreign');
			$table->dropForeign('quotations_cancel_quotation_by_foreign');
			$table->dropForeign('quotations_company_id_foreign');
			$table->dropForeign('quotations_contract_by_foreign');
			$table->dropForeign('quotations_created_by_foreign');
			$table->dropForeign('quotations_customer_id_foreign');
			$table->dropForeign('quotations_customer_stage_id_foreign');
			$table->dropForeign('quotations_lead_id_foreign');
			$table->dropForeign('quotations_parent_id_foreign');
			$table->dropForeign('quotations_piece_id_foreign');
			$table->dropForeign('quotations_sales_id_foreign');
			$table->dropForeign('quotations_stop_contract_by_foreign');
			$table->dropForeign('quotations_submit_by_foreign');
		});
	}

}
