<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifyInvoiceVendorDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::delete("DELETE FROM invoice_vendor_details WHERE id IS NOT NULL");
        Schema::table('invoice_vendor_details', function (Blueprint $table) {
          $table->foreign('header_id')->references('id')->on('invoice_vendors')->onDelete('cascade');
          $table->foreign('payable_detail_id')->references('id')->on('payable_details')->onDelete('set null');
          $table->unsignedInteger('manifest_cost_id')->nullable();
          $table->unsignedInteger('job_order_cost_id')->nullable();
          $table->unsignedInteger('payable_detail_id')->nullable();
          $table->double('ppn')->default(0);
          $table->foreign('manifest_cost_id')->references('id')->on('manifest_costs')->onDelete('restrict');
          $table->foreign('job_order_cost_id')->references('id')->on('job_order_costs')->onDelete('restrict');
          $table->dropColumn(['payable_id','journal_id','nota_account_id','verification','tax_value','tax_type','subtotal','margin','is_consistent','type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_vendor_details', function (Blueprint $table) {
            //
        });
    }
}
