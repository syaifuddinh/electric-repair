<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateClaimsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('journal_id')->nullable();
            $table->date('date_transaction');
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('collectible_id')->nullable();
            $table->unsignedInteger('job_order_id')->nullable();
            $table->unsignedInteger('sales_order_id')->nullable();
            $table->integer('claim_type')->default(1)->comment('1 = Driver, 2 = Vendor Kendaraan, 3 = Internal');
            $table->unsignedInteger('driver_id')->nullable();
            $table->unsignedInteger('vendor_id')->nullable();
            $table->unsignedInteger('payable_id')->nullable();
            $table->unsignedInteger('receivable_id')->nullable();
            $table->boolean('status')->default(1);
            $table->string('code', 50)->nullable()->index();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
            $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('restrict');
            $table->foreign('collectible_id')->references('id')->on('contacts')->onDelete('restrict');
            $table->foreign('driver_id')->references('id')->on('contacts')->onDelete('restrict');
            $table->foreign('vendor_id')->references('id')->on('contacts')->onDelete('restrict');
            $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('restrict');
            $table->foreign('sales_order_id')->references('id')->on('sales_orders')->onDelete('restrict');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('restrict');
            $table->foreign('payable_id')->references('id')->on('payables')->onDelete('restrict');
            $table->foreign('receivable_id')->references('id')->on('receivables')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('claims', function (Blueprint $table){
            $table->dropForeign('claims_company_id_foreign');
            $table->dropForeign('claims_customer_id_foreign');
            $table->dropForeign('claims_collectible_id_foreign');
            $table->dropForeign('claims_driver_id_foreign');
            $table->dropForeign('claims_vendor_id_foreign');
            $table->dropForeign('claims_job_order_id_foreign');
            $table->dropForeign('claims_sales_order_id_foreign');
            $table->dropForeign('claims_journal_id_foreign');
            $table->dropForeign('claims_payable_id_foreign');
            $table->dropForeign('claims_receivable_id_foreign');
        });
        
        Schema::dropIfExists('claims');
    }
}
