<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInvoiceVendorsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('invoice_vendors', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('vendor_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->date('date_invoice');
			$table->date('date_receive');
			$table->float('total', 10, 0)->default(0);
			$table->text('description', 65535)->nullable();
			$table->integer('status')->default(1);
			$table->timestamps();
			$table->string('slug', 191)->nullable();
			$table->integer('status_approve')->default(0);
			$table->date('due_date');
			$table->integer('journal_id')->unsigned()->nullable()->index();
			$table->integer('payable_id')->unsigned()->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('invoice_vendors');
	}

}
