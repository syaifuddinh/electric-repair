<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class WarehouseReceiptDetailInStokOnameDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('stok_opname_warehouse_details', 'warehouse_stock_detail_id')) {

            Schema::table('stok_opname_warehouse_details', function (Blueprint $table) {
                $table->dropForeign(['warehouse_stock_detail_id']);
                $table->dropColumn(['warehouse_stock_detail_id']);
            });
        }
        Schema::table('stok_opname_warehouse_details', function (Blueprint $table) {
            // $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(true)->index();
            // $table->unsignedInteger('requested_stock_transaction_id')->nullable(true)->index();
            // $table->unsignedInteger('adjustment_stock_transaction_id')->nullable(true)->index();

            // $table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onDelete('restrict');
            // $table->foreign('requested_stock_transaction_id')->references('id')->on('stock_transactions')->onDelete('set null');
            // $table->foreign('adjustment_stock_transaction_id')->references('id')->on('stock_transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stok_opname_warehouse_details', function (Blueprint $table) {
            $table->dropForeign(['warehouse_receipt_detail_id']);
            $table->dropColumn(['warehouse_receipt_detail_id']);

            $table->dropForeign(['requested_stock_transaction_id']);
            $table->dropColumn(['requested_stock_transaction_id']);
            
            $table->dropForeign(['adjustment_stock_transaction_id']);
            $table->dropColumn(['adjustment_stock_transaction_id']);
        });
    }
}
