<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class StockTransactionInUsingItemDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('using_item_details', function (Blueprint $table) {
            $table->unsignedInteger('requested_stock_transaction_id')->nullable(true)->index();
            $table->unsignedInteger('outbound_stock_transaction_id')->nullable(true)->index();

            $table->foreign('requested_stock_transaction_id')->references('id')->on('stock_transactions')->onDelete('set null');
            $table->foreign('outbound_stock_transaction_id')->references('id')->on('stock_transactions')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('using_item_details', function (Blueprint $table) {
            $table->dropForeign(['requested_stock_transaction_id']);
            $table->dropForeign(['outbound_stock_transaction_id']);
            $table->dropColumn(['requested_stock_transaction_id']);
            $table->dropColumn(['outbound_stock_transaction_id']);
        });
    }
}
