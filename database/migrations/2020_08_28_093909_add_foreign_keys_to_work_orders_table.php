<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToWorkOrdersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('work_orders', function(Blueprint $table)
		{
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('price_list_id')->references('id')->on('price_lists')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('quotation_detail_id')->references('id')->on('quotation_details')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('quotation_id')->references('id')->on('quotations')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('updated_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('work_orders', function(Blueprint $table)
		{
			$table->dropForeign('work_orders_company_id_foreign');
			$table->dropForeign('work_orders_customer_id_foreign');
			$table->dropForeign('work_orders_price_list_id_foreign');
			$table->dropForeign('work_orders_quotation_detail_id_foreign');
			$table->dropForeign('work_orders_quotation_id_foreign');
			$table->dropForeign('work_orders_updated_by_foreign');
		});
	}

}
