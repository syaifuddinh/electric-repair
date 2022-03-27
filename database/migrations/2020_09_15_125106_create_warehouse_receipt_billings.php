<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class CreateWarehouseReceiptBillings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('warehouse_receipt_billings')) {
            Schema::create('warehouse_receipt_billings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('warehouse_receipt_id')
                ->nullable(false)
                ->index();
                $table->unsignedInteger('job_order_id')
                ->nullable(true)
                ->index();
                $table->date('billing_date')
                ->nullable(true);
                $table->date('new_receive_date')
                ->nullable(false);
                $table->timestamps();
                $table->foreign('job_order_id')->references('id')->on('job_orders')->onDelete('cascade');
                $table->foreign('warehouse_receipt_id')->references('id')->on('warehouse_receipts')->onDelete('cascade');
            });
        }
        Schema::table('job_order_details', function (Blueprint $table) {
            $table->date('receive_date')->nullable(true);
        });

        $jobOrderDetails = DB::table('job_order_details')
        ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id')
        ->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
        ->select('job_order_details.id', 'warehouse_receipts.receive_date')
        ->get();
        foreach ($jobOrderDetails as $item) {
            DB::table('job_order_details')
            ->whereId($item->id)
            ->update([
                'receive_date' => $item->receive_date
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('warehouse_receipt_billings');
        Schema::table('job_order_details', function (Blueprint $table) {
            $table->dropColumn(['inbound_date']);
        });
    }
}
