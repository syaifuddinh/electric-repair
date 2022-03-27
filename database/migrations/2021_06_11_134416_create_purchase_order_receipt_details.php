<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePurchaseOrderReceiptDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_order_receipt_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger("warehouse_receipt_detail_id")->nullable(false)->index();
            $table->unsignedInteger("purchase_order_detail_id")->nullable(false)->index();
            $table->double("average_price")->nullable(false)->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_order_receipt_details');
    }
}
