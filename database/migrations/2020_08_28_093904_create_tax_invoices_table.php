<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTaxInvoicesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('tax_invoices', function(Blueprint $table)
		{
			$table->bigInteger('id', true)->unsigned();
			$table->string('code');
			$table->date('expiry_date');
			$table->integer('invoice_id')->unsigned()->nullable()->index();
			$table->timestamps();
			$table->integer('is_active')->default(1)->index();
			$table->date('start_date')->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('tax_invoices');
	}

}
