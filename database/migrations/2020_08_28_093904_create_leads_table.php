<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLeadsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('leads', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('city_id')->unsigned();
			$table->integer('company_id')->unsigned();
			$table->integer('lead_status_id')->unsigned();
			$table->integer('lead_source_id')->unsigned();
			$table->integer('industry_id')->unsigned();
			$table->integer('sales_id')->unsigned();
			$table->integer('create_by')->unsigned();
			$table->string('code', 191);
			$table->string('name', 191);
			$table->string('address', 191)->nullable();
			$table->string('postal_code', 191)->nullable();
			$table->string('phone', 191)->nullable();
			$table->string('phone2', 191)->nullable();
			$table->string('email', 191)->nullable();
			$table->string('contact_person', 191)->nullable();
			$table->string('contact_person_email', 191)->nullable();
			$table->string('contact_person_phone', 191)->nullable();
			$table->boolean('is_contact')->default(0);
			$table->boolean('is_active')->default(1);
			$table->timestamps();
			$table->integer('inquery_id')->unsigned()->nullable();
			$table->integer('quotation_id')->unsigned()->nullable();
			$table->integer('step')->default(1);
			$table->integer('cancel_by')->unsigned()->nullable();
			$table->dateTime('cancel_date')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('leads');
	}

}
