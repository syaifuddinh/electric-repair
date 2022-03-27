<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToLeadsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('leads', function(Blueprint $table)
		{
			$table->foreign('cancel_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('city_id')->references('id')->on('cities')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('industry_id')->references('id')->on('industries')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('inquery_id')->references('id')->on('inqueries')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('lead_source_id')->references('id')->on('lead_sources')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('lead_status_id')->references('id')->on('lead_statuses')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('quotation_id')->references('id')->on('quotations')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('sales_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('leads', function(Blueprint $table)
		{
			$table->dropForeign('leads_cancel_by_foreign');
			$table->dropForeign('leads_city_id_foreign');
			$table->dropForeign('leads_company_id_foreign');
			$table->dropForeign('leads_create_by_foreign');
			$table->dropForeign('leads_industry_id_foreign');
			$table->dropForeign('leads_inquery_id_foreign');
			$table->dropForeign('leads_lead_source_id_foreign');
			$table->dropForeign('leads_lead_status_id_foreign');
			$table->dropForeign('leads_quotation_id_foreign');
			$table->dropForeign('leads_sales_id_foreign');
		});
	}

}
