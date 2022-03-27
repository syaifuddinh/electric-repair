
<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveReceiptListDetailIdInReturDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('retur_details', 'receipt_list_detail_id')) {
            Schema::table('retur_details', function (Blueprint $table) {
                $table->dropForeign(['receipt_list_detail_id']);
                $table->dropColumn(['receipt_list_detail_id']);
            });
        }

        Schema::table('retur_details', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(false)->index();
            $table->unsignedInteger('rack_id')->nullable(false)->index();
            $table->unsignedInteger('requested_stock_transaction_id')->nullable(true)->index();
            $table->unsignedInteger('outbound_stock_transaction_id')->nullable(true)->index();

            // $table->foreign('warehouse_receipt_detail_id')->references('id')->on("warehouse_receipt_details")->onDelete("restrict");
            // $table->foreign('rack_id')->references('id')->on("racks")->onDelete("restrict");

            // $table->foreign('requested_stock_transaction_id')->references('id')->on("stock_transactions")->onDelete("set null");
            
            // $table->foreign('outbound_stock_transaction_id')->references('id')->on("stock_transactions")->onDelete("set null");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(!Schema::hasColumn('retur_details', 'receipt_list_detail_id')) {
            Schema::table('retur_details', function (Blueprint $table) {
                $table->unsignedInteger('receipt_list_detail_id')->nullable(true);
            });
        }

        Schema::table('retur_details', function (Blueprint $table) {
            $table->dropForeign(['warehouse_receipt_detail_id']);
            $table->dropColumn(['warehouse_receipt_detail_id']);

            $table->dropForeign(['rack_id']);
            $table->dropColumn(['rack_id']);
            
            $table->dropForeign(['requested_stock_transaction_id']);
            $table->dropColumn(['requested_stock_transaction_id']);
            
            $table->dropForeign(['outbound_stock_transaction_id']);
            $table->dropColumn(['outbound_stock_transaction_id']);
        });
    }
}
