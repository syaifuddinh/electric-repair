<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryOrderDriverDocument extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('delivery_order_driver_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('delivery_order_driver_id')->nullable(false)->index(); 
            $table->string('file')->nullable(false);
            $table->string('filename')->nullable(false);

            $table->foreign('delivery_order_driver_id')->references('id')->on('delivery_order_drivers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_order_driver_documents');
    }
}
