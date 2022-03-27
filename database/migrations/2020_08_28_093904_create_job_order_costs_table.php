<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateJobOrderCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('job_order_costs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('cost_type_id')->unsigned();
			$table->integer('transaction_type_id')->unsigned()->nullable();
			$table->integer('pickup_id')->unsigned()->nullable();
			$table->integer('manifest_id')->unsigned()->nullable();
			$table->integer('vendor_id')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->float('qty', 10, 0)->default(0);
			$table->float('price', 10, 0)->default(0);
			$table->float('total_price', 10, 0)->default(0);
			$table->text('description', 65535)->nullable();
			$table->timestamps();
			$table->integer('status')->default(1);
			$table->float('quotation_costs', 20)->default(0.00);
			$table->float('before_revision_cost', 20)->default(0.00);
			$table->integer('manifest_cost_id')->unsigned()->nullable();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('type')->default(1);
			$table->string('slug', 191)->nullable();
			$table->boolean('is_edit')->default(0);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('job_order_costs');
	}

}
