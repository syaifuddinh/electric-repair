<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomFieldsToContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('category')->nullable()->comment('option: individual, company')->after('vendor_type_id');
            $table->string('contact_person_no_2')->nullable()->after('contact_person_no');
            $table->string('contact_person_position')->nullable()->after('contact_person_no_2');
            $table->string('contact_person_npwp')->nullable()->after('contact_person_position');
            $table->string('no_tdp')->nullable();
            $table->string('no_siup')->nullable();
            $table->string('no_sppkp')->nullable();
            $table->string('website')->nullable();
            $table->string('position')->comment('nama jabatan')->nullable();
            $table->string('purchase_purpose')->nullable();
            $table->string('personal_facebook_account')->nullable();
            $table->string('personal_instagram_account')->nullable();
            $table->string('company_facebook_account')->nullable();
            $table->string('company_instagram_account')->nullable();
            $table->string('company_customer_service')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('category');
            $table->dropColumn('contact_person_no_2');
            $table->dropColumn('contact_person_position');
            $table->dropColumn('contact_person_npwp');
            $table->dropColumn('no_tdp');
            $table->dropColumn('no_siup');
            $table->dropColumn('no_sppkp');
            $table->dropColumn('website');
            $table->dropColumn('position');
            $table->dropColumn('purchase_purpose');
            $table->dropColumn('personal_facebook_account');
            $table->dropColumn('personal_instagram_account');
            $table->dropColumn('company_facebook_account');
            $table->dropColumn('company_instagram_account');
            $table->dropColumn('company_customer_service');
        });
    }
}
