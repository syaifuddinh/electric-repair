<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUmSupplierPaidsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('um_supplier_paids', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('type_paid')->unsigned();
			$table->integer('type_transaction_id')->unsigned();
			$table->integer('reff_id')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->float('amount', 10, 0)->default(0);
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
		Schema::drop('um_supplier_paids');
	}

}
