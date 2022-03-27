<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('contacts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('company_id')->unsigned();
			$table->string('code', 191)->nullable();
			$table->string('name', 191);
			$table->string('address', 191);
			$table->integer('city_id')->unsigned()->nullable();
			$table->string('postal_code', 191)->nullable();
			$table->string('phone', 191)->nullable();
			$table->string('phone2', 191)->nullable();
			$table->string('fax', 191)->nullable();
			$table->string('email', 191)->nullable();
			$table->string('contact_person', 191)->nullable();
			$table->string('contact_person_email', 191)->nullable();
			$table->string('contact_person_no', 191)->nullable();
			$table->string('pegawai_no', 191)->nullable();
			$table->integer('vendor_type_id')->unsigned()->nullable();
			$table->boolean('is_pegawai')->default(0);
			$table->boolean('is_investor')->default(0);
			$table->boolean('is_pelanggan')->default(0);
			$table->boolean('is_asuransi')->default(0);
			$table->boolean('is_supplier')->default(0);
			$table->boolean('is_depo_bongkar')->default(0);
			$table->boolean('is_helper')->default(0);
			$table->boolean('is_driver')->default(0);
			$table->boolean('is_vendor')->default(0);
			$table->boolean('is_sales')->default(0);
			$table->boolean('is_kurir')->default(0);
			$table->boolean('is_pengirim')->default(0);
			$table->boolean('is_penerima')->default(0);
			$table->integer('akun_hutang')->unsigned()->nullable();
			$table->integer('akun_piutang')->unsigned()->nullable();
			$table->integer('akun_um_supplier')->unsigned()->nullable();
			$table->integer('akun_um_customer')->unsigned()->nullable();
			$table->integer('term_of_payment')->nullable();
			$table->integer('limit_piutang')->nullable();
			$table->integer('limit_hutang')->nullable();
			$table->string('npwp', 191)->nullable();
			$table->boolean('pkp')->nullable();
			$table->integer('tax_id')->unsigned()->nullable();
			$table->string('description', 191)->nullable();
			$table->string('rek_no', 191)->nullable();
			$table->string('rek_milik', 191)->nullable();
			$table->integer('rek_bank_id')->unsigned()->nullable();
			$table->string('rek_cabang', 191)->nullable();
			$table->timestamps();
			$table->boolean('is_active')->nullable()->default(1);
			$table->dateTime('vendor_register_date')->nullable();
			$table->boolean('vendor_status_approve')->nullable()->default(2);
			$table->dateTime('vendor_approve_date')->nullable();
			$table->dateTime('vendor_reject_register_date')->nullable();
			$table->integer('vendor_user_approve')->unsigned()->nullable();
			$table->integer('driver_status')->nullable();
			$table->integer('vehicle_id')->unsigned()->nullable();
			$table->boolean('is_internal')->nullable()->default(1);
			$table->integer('address_type_id')->unsigned()->nullable();
			$table->string('file_name', 191)->nullable();
			$table->string('file_extension', 191)->nullable();
			$table->string('ktp_file_name', 191)->nullable();
			$table->string('ktp_file_extension', 191)->nullable();
			$table->string('npwp_file_name', 191)->nullable();
			$table->string('npwp_file_extension', 191)->nullable();
			$table->integer('parent_id')->unsigned()->nullable();
			$table->string('password', 191)->nullable();
			$table->string('api_token', 191)->nullable();
			$table->dateTime('last_login')->nullable();
			$table->dateTime('last_update')->nullable();
			$table->integer('is_staff_gudang')->nullable()->default(0);
			$table->string('customer_service_name', 200);
			$table->string('sales_name', 200);
			$table->integer('is_approved_customer')->default(0);
			$table->integer('sales_id')->unsigned()->nullable()->index();
			$table->integer('customer_service_id')->unsigned()->nullable()->index();
			$table->string('no_ktp', 50)->nullable();
			$table->string('npwp_cabang', 191)->nullable()->unique();
			$table->text('additional')->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('contacts');
	}

}
