<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPpnTotalInvoiceVendor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoice_vendors', function (Blueprint $table) {
          $table->double('subtotal')->default(0);
          $table->double('ppn')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_vendors', function (Blueprint $table) {
          $table->dropColumn(['subtotal','ppn']);
        });
    }
}
