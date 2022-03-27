<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNewfieldsToCustomerOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('customer_order_details', function (Blueprint $table) {
            $table->unsignedInteger('rack_id')->nullable();
            $table->unsignedInteger('warehouse_receipt_detail_id')->nullable();
            $table->unsignedInteger('requested_stock_transaction_id')->nullable();

            $table->foreign('rack_id')->references('id')->on('racks')->onDelete('restrict');
            $table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onDelete('restrict');
            $table->foreign('requested_stock_transaction_id')->references('id')->on('stock_transactions')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_order_details', function (Blueprint $table) {
            $table->dropForeign('customer_order_details_rack_id_foreign');
            $table->dropForeign('customer_order_details_warehouse_receipt_detail_id_foreign');
            $table->dropForeign('customer_order_details_requested_stock_transaction_id_foreign');
            $table->dropColumn(['rack_id', 'warehouse_receipt_detail_id', 'requested_stock_transaction_id']);
        });
    }
}
