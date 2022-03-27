<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Abstracts\Inventory\ReceiptQualityStatus;

class ReceiptQualityStatusInWarehouseReceiptDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouse_receipt_details', function (Blueprint $table) {
            $table->unsignedInteger('receipt_quality_status')->nullable(true)->index();
            $table->foreign('receipt_quality_status')->references('id')->on('receipt_quality_statuses')->onDelete('restrict');
        });

        DB::table('warehouse_receipt_details')
        ->update([
            'receipt_quality_status' => ReceiptQualityStatus::getDraft()
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouse_receipt_details', function (Blueprint $table) {
            $table->dropForeign(['receipt_quality_status']);
            $table->dropColumn(['receipt_quality_status']);
        });
    }
}
