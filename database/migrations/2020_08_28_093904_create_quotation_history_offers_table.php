<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQuotationHistoryOffersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quotation_history_offers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('quotation_detail_id')->unsigned();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('reject_by')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('status')->default(1);
			$table->boolean('is_approve')->default(0);
			$table->float('price', 10, 0)->default(0);
			$table->float('total_cost', 10, 0)->default(0);
			$table->float('total_offering', 10, 0)->default(0);
			$table->dateTime('date_approve')->nullable();
			$table->dateTime('date_reject')->nullable();
			$table->timestamps();
			$table->string('slug', 191)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('quotation_history_offers');
	}

}
