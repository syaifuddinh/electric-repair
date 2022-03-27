<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAssetDepreciationsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('asset_depreciations', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('header_id')->unsigned();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->date('date_approve')->nullable();
			$table->date('date_utility');
			$table->float('depreciation_cost', 20)->default(0.00);
			$table->integer('status')->default(1);
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
		Schema::drop('asset_depreciations');
	}

}
