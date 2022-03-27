<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePrintRemarksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('print_remarks', function(Blueprint $table)
		{
			$table->increments('id');
			$table->text('account', 65535)->nullable();
			$table->text('bank', 65535)->nullable();
			$table->text('person', 65535)->nullable();
			$table->text('signature', 65535)->nullable();
			$table->text('position', 65535)->nullable();
			$table->text('attn', 65535)->nullable();
			$table->text('address', 65535)->nullable();
			$table->text('phone', 65535)->nullable();
			$table->text('fax', 65535)->nullable();
			$table->string('logo', 191)->nullable();
			$table->string('email', 100)->nullable();
			$table->string('sms_center', 100)->nullable();
			$table->text('additional', 65535)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('print_remarks');
	}

}
