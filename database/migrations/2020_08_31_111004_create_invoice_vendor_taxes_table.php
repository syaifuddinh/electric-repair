<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvoiceVendorTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_vendor_taxes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('header_id');
            $table->unsignedInteger('detail_id');
            $table->unsignedInteger('tax_id')->nullable();
            $table->double('amount')->default(0);
            $table->timestamps();

            $table->foreign('header_id')->references('id')->on('invoice_vendors')->onDelete('cascade');
            $table->foreign('detail_id')->references('id')->on('invoice_vendor_details')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxes')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoice_vendor_taxes');
    }
}
