<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateQuotationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('quotations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('lead_id')->unsigned()->nullable();
			$table->integer('customer_id')->unsigned()->nullable();
			$table->integer('sales_id')->unsigned()->nullable();
			$table->integer('customer_stage_id')->unsigned();
			$table->integer('created_by')->unsigned();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('contract_by')->unsigned()->nullable();
			$table->integer('cancel_by')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->string('no_inquery', 191)->nullable();
			$table->date('date_inquery')->nullable();
			$table->date('date_approve')->nullable();
			$table->string('no_contract', 191)->nullable();
			$table->date('date_contract')->nullable();
			$table->date('date_start_contract')->nullable();
			$table->date('date_end_contract')->nullable();
			$table->date('date_cancel')->nullable();
			$table->boolean('is_contract')->default(0);
			$table->boolean('is_active')->default(1);
			$table->boolean('is_cancel')->default(0);
			$table->float('sales_commision', 10, 0)->default(0);
			$table->integer('bill_type');
			$table->integer('send_type');
			$table->float('price_full_inquery', 10, 0)->default(0);
			$table->float('price_full_contract', 10, 0)->default(0);
			$table->text('description_inquery', 65535)->nullable();
			$table->text('description_contract', 65535)->nullable();
			$table->integer('type_entry');
			$table->timestamps();
			$table->string('name', 191)->nullable();
			$table->integer('submit_by')->unsigned()->nullable();
			$table->integer('approve_direction_by')->unsigned()->nullable();
			$table->integer('approve_manager_by')->unsigned()->nullable();
			$table->integer('status_approve')->default(1)->comment('Lead, Opportunity, Inquery, Quotation, Kontrak');
			$table->text('description_stop_contract', 65535)->nullable();
			$table->integer('stop_contract_by')->unsigned()->nullable();
			$table->date('date_stop_contract')->nullable();
			$table->text('description_amandemen', 65535)->nullable();
			$table->date('date_amandemen')->nullable();
			$table->integer('parent_id')->unsigned()->nullable();
			$table->boolean('is_hide')->default(0);
			$table->integer('cancel_quotation_by')->unsigned()->nullable();
			$table->dateTime('cancel_quotation_date')->nullable();
			$table->integer('imposition')->nullable();
			$table->integer('piece_id')->unsigned()->nullable();
			$table->string('slug', 191)->nullable();
			$table->string('path', 50)->nullable()->index();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('quotations');
	}

}
