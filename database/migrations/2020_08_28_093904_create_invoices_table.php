<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('invoices', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('account_cash_id')->unsigned()->nullable();
			$table->integer('account_receivable_id')->unsigned()->nullable();
			$table->integer('account_selling_id')->unsigned()->nullable();
			$table->integer('type_transaction_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('receivable_id')->unsigned()->nullable();
			$table->integer('customer_id')->unsigned();
			$table->integer('tax_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->string('code', 191)->nullable();
			$table->integer('termin')->nullable();
			$table->date('date_invoice');
			$table->date('due_date')->nullable();
			$table->integer('status')->default(1);
			$table->text('description', 65535)->nullable();
			$table->float('sub_total', 10, 0)->default(0);
			$table->float('discount_percent', 10, 0)->default(0);
			$table->float('discount_total', 10, 0)->default(0);
			$table->boolean('is_ppn');
			$table->float('total_another_ppn', 10, 0)->default(0);
			$table->float('grand_total', 10, 0)->default(0);
			$table->float('sub_total_additional', 10, 0)->default(0);
			$table->float('discount_percent_additional', 10, 0)->default(0);
			$table->float('discount_total_additional', 10, 0)->default(0);
			$table->float('grand_total_additional', 10, 0)->default(0);
			$table->timestamps();
			$table->boolean('type_bayar')->default(1);
			$table->boolean('is_approve')->default(0);
			$table->integer('approve_by')->unsigned()->nullable();
			$table->dateTime('date_approve')->nullable();
			$table->date('journal_date')->nullable();
			$table->string('slug', 191)->nullable();
			$table->boolean('is_join')->default(0);
			$table->integer('serial')->unsigned()->nullable();
			$table->string('printed_amount', 100)->nullable();
			$table->integer('qty_edit')->default(0)->index();
			$table->integer('qty_batal_posting')->default(0)->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('invoices');
	}

}
