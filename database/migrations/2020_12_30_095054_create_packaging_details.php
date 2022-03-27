<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackagingDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('packaging_details');
        Schema::create('packaging_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('packaging_id')->nullable(false)->index();
            $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(false);
            $table->integer('qty')->nullable(false)->default(0);
            $table->timestamps();

            $table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('packaging_details');
    }
}
