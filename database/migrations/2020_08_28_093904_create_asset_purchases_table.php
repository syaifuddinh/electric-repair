<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAssetPurchasesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('asset_purchases', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('supplier_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->date('date_transaction');
			$table->string('code', 191)->nullable();
			$table->integer('status')->default(1);
			$table->integer('termin');
			$table->integer('tempo')->nullable();
			$table->integer('cash_account_id')->unsigned()->nullable();
			$table->float('total_price', 20)->default(0.00);
			$table->text('description', 65535)->nullable();
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
		Schema::drop('asset_purchases');
	}

}
