<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SalesOrderDetailIdInSalesOrderReceipt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('sales_order_returns')->delete();
        DB::table('sales_order_return_details')->delete();
        DB::table('sales_order_return_receipts')->delete();

        Schema::table('sales_order_return_receipts', function (Blueprint $table) {
            $table->unsignedInteger('sales_order_return_detail_id')->nullable(false)->index();
            $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(false)->index();

            $table->foreign('sales_order_return_detail_id')->references('id')->on('sales_order_return_details')->onDelete('restrict');
            $table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_order_return_receipts', function (Blueprint $table) {
            $table->dropForeign(['sales_order_return_detail_id']);
            $table->dropForeign(['warehouse_receipt_detail_id']);

            $table->dropColumn(['sales_order_return_detail_id']);
            $table->dropColumn(['warehouse_receipt_detail_id']);
        });
    }
}
