<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToTaxInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('tax_invoices', function(Blueprint $table)
		{
			$table->foreign('invoice_id')->references('id')->on('invoices')->onUpdate('RESTRICT')->onDelete('CASCADE');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('tax_invoices', function(Blueprint $table)
		{
			$table->dropForeign('tax_invoices_invoice_id_foreign');
		});
	}

}
