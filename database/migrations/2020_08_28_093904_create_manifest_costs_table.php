<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateManifestCostsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('manifest_costs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('company_id')->unsigned();
			$table->integer('cost_type_id')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('vendor_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->float('qty', 10, 0)->default(0);
			$table->float('price', 10, 0)->default(0);
			$table->float('total_price', 10, 0)->default(0);
			$table->string('description', 191)->nullable();
			$table->boolean('is_generated')->default(1);
			$table->boolean('is_internal')->default(1);
			$table->integer('status')->default(1);
			$table->timestamps();
			$table->float('quotation_costs', 20)->default(0.00);
			$table->float('before_revision_cost', 20)->default(0.00);
			$table->integer('type')->default(1)->index();
			$table->integer('is_edit')->default(0)->index();
			$table->integer('approve_by')->unsigned()->nullable()->index();
			$table->integer('transaction_type_id')->unsigned()->nullable()->index();
			$table->string('slug', 50)->nullable()->index();
			$table->integer('job_order_cost_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('manifest_costs');
	}

}
