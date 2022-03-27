<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ApprovedStockTransactionInManifestDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manifest_details', function (Blueprint $table) {
            $table->unsignedInteger('outbound_stock_transaction_id')->nullable(true)->index();

            $table->foreign('outbound_stock_transaction_id')->references('id')->on('stock_transactions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manifest_details', function (Blueprint $table) {
            $table->dropForeign(['outbound_stock_transaction_id']);
            $table->dropColumn(['outbound_stock_transaction_id']);
        });
    }
}
