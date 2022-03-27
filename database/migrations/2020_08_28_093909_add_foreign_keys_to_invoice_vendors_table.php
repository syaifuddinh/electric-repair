<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToInvoiceVendorsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('invoice_vendors', function(Blueprint $table)
		{
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('create_by')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('journal_id')->references('id')->on('journals')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('payable_id')->references('id')->on('payables')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vendor_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('invoice_vendors', function(Blueprint $table)
		{
			$table->dropForeign('invoice_vendors_company_id_foreign');
			$table->dropForeign('invoice_vendors_create_by_foreign');
			$table->dropForeign('invoice_vendors_journal_id_foreign');
			$table->dropForeign('invoice_vendors_payable_id_foreign');
			$table->dropForeign('invoice_vendors_vendor_id_foreign');
		});
	}

}
