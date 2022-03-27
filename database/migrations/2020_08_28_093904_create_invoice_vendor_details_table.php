<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInvoiceVendorDetailsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('invoice_vendor_details', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('payable_id')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('nota_account_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->string('reff_no', 191)->nullable();
			$table->float('verification', 10, 0)->default(0);
			$table->string('tax_value', 191)->nullable();
			$table->string('tax_type', 191)->nullable();
			$table->float('diskon', 10, 0)->default(0);
			$table->float('subtotal', 10, 0)->default(0);
			$table->float('total', 10, 0)->default(0);
			$table->float('margin', 10, 0)->default(0);
			$table->boolean('is_consistent')->default(0);
			$table->text('description', 65535)->nullable();
			$table->integer('type')->default(1);
			$table->timestamps();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('invoice_vendor_details');
	}

}
