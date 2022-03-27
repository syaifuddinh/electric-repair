<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesOrderReturnReceipt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_order_return_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('sales_order_return_id')->nullable(false)->index();
            $table->unsignedInteger('warehouse_receipt_id')->nullable(false)->index();

            $table->foreign('sales_order_return_id')->references('id')->on('sales_order_returns')->onDelete('cascade');
            $table->foreign('warehouse_receipt_id')->references('id')->on('warehouse_receipts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales_order_return_receipts');
    }
}
