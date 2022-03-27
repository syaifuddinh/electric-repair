<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToInqueriesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('inqueries', function(Blueprint $table)
		{
			$table->foreign('cancel_inquery_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('cancel_opportunity_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_stage_id')->references('id')->on('customer_stages')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('inquery_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('lead_id')->references('id')->on('leads')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('opportunity_id')->references('id')->on('inqueries')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('quotation_id')->references('id')->on('quotations')->onUpdate('RESTRICT')->onDelete('CASCADE');
			$table->foreign('sales_inquery_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('sales_opportunity_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('inqueries', function(Blueprint $table)
		{
			$table->dropForeign('inqueries_cancel_inquery_by_foreign');
			$table->dropForeign('inqueries_cancel_opportunity_by_foreign');
			$table->dropForeign('inqueries_company_id_foreign');
			$table->dropForeign('inqueries_create_by_foreign');
			$table->dropForeign('inqueries_customer_id_foreign');
			$table->dropForeign('inqueries_customer_stage_id_foreign');
			$table->dropForeign('inqueries_inquery_by_foreign');
			$table->dropForeign('inqueries_lead_id_foreign');
			$table->dropForeign('inqueries_opportunity_id_foreign');
			$table->dropForeign('inqueries_quotation_id_foreign');
			$table->dropForeign('inqueries_sales_inquery_id_foreign');
			$table->dropForeign('inqueries_sales_opportunity_id_foreign');
		});
	}

}
