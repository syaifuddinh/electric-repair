<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSubmissionCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('submission_costs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('relation_cost_id')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('type_transaction_id')->unsigned();
			$table->integer('vendor_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('cancel_by')->unsigned()->nullable();
			$table->date('date_submission');
			$table->date('date_approve')->nullable();
			$table->date('date_cancel')->nullable();
			$table->integer('status')->default(1);
			$table->integer('type_submission')->default(1);
			$table->text('description', 65535)->nullable();
			$table->timestamps();
			$table->integer('revision_by')->unsigned()->nullable();
			$table->integer('approve_revision_by')->unsigned()->nullable();
			$table->dateTime('date_approve_revision')->nullable();
			$table->integer('journal_posting_id')->unsigned()->nullable();
			$table->integer('payable_id')->unsigned()->nullable();
			$table->integer('cash_transaction_id')->unsigned()->nullable();
			$table->dateTime('revision_date')->nullable();
			$table->string('slug')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('submission_costs');
	}

}
