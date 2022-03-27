<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToContactsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('contacts', function(Blueprint $table)
		{
			$table->foreign('akun_hutang')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('akun_piutang')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('akun_um_customer')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('akun_um_supplier')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('city_id')->references('id')->on('cities')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('company_id')->references('id')->on('companies')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('customer_service_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('parent_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('rek_bank_id')->references('id')->on('banks')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('sales_id')->references('id')->on('contacts')->onUpdate('RESTRICT')->onDelete('SET NULL');
			$table->foreign('tax_id')->references('id')->on('taxes')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('vendor_type_id')->references('id')->on('vendor_types')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('contacts', function(Blueprint $table)
		{
			$table->dropForeign('contacts_akun_hutang_foreign');
			$table->dropForeign('contacts_akun_piutang_foreign');
			$table->dropForeign('contacts_akun_um_customer_foreign');
			$table->dropForeign('contacts_akun_um_supplier_foreign');
			$table->dropForeign('contacts_city_id_foreign');
			$table->dropForeign('contacts_company_id_foreign');
			$table->dropForeign('contacts_customer_service_id_foreign');
			$table->dropForeign('contacts_parent_id_foreign');
			$table->dropForeign('contacts_rek_bank_id_foreign');
			$table->dropForeign('contacts_sales_id_foreign');
			$table->dropForeign('contacts_tax_id_foreign');
			$table->dropForeign('contacts_vendor_type_id_foreign');
		});
	}

}
