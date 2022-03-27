<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVoyageReceipts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('voyage_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('voyage_schedule_id')->nullable(false)->index();
            $table->unsignedInteger('warehouse_receipt_id')->nullable(false)->index();

            $table->foreign('voyage_schedule_id')->references('id')->on('voyage_schedules')->onDelete('cascade');
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
        Schema::dropIfExists('voyage_receipts');
    }
}
