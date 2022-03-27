<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAssetAfkirsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('asset_afkirs', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->integer('asset_id')->unsigned();
			$table->integer('account_loss_id')->unsigned();
			$table->integer('journal_id')->unsigned()->nullable();
			$table->integer('create_by')->unsigned();
			$table->integer('approve_by')->unsigned()->nullable();
			$table->string('code', 191)->nullable();
			$table->date('approve_date')->nullable();
			$table->date('date_transaction');
			$table->integer('status')->default(1);
			$table->float('loss_amount', 20)->default(0.00);
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
		Schema::drop('asset_afkirs');
	}

}
