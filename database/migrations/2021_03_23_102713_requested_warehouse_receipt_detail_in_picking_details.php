<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RequestedWarehouseReceiptDetailInPickingDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('picking_details', function (Blueprint $table) {
            $table->unsignedInteger('requested_stock_transaction_id')->nullable(true)->index();
            $table->unsignedInteger('approved_stock_transaction_id')->nullable(true)->index();

            $table->foreign('requested_stock_transaction_id')->references('id')->on('stock_transactions')->onDelete('RESTRICT');
            $table->foreign('approved_stock_transaction_id')->references('id')->on('stock_transactions')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('picking_details', function (Blueprint $table) {
            $table->dropForeign(['approved_stock_transaction_id']);
            $table->dropColumn(['approved_stock_transaction_id']);
            $table->dropForeign(['requested_stock_transaction_id']);
            $table->dropColumn(['requested_stock_transaction_id']);
        });
    }
}
