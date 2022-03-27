<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClosingTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('closing', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->date('start_periode');
			$table->date('end_periode');
			$table->date('closing_date');
			$table->text('description', 65535)->nullable();
			$table->integer('status')->default(0);
			$table->string('code', 191)->nullable();
			$table->boolean('is_lock')->default(0);
			$table->boolean('is_depresiasi')->default(0);
			$table->boolean('is_revaluasi')->default(0);
			$table->timestamps();
			$table->integer('journal_id')->unsigned()->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('closing');
	}

}
