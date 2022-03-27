<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackagingItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('packaging_items');
        Schema::create('packaging_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('packaging_id')->nullable(false)->index();
            $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(true);
            $table->string('item_name', 100)->nullable(false);
            $table->integer('qty')->nullable(false)->default(0);

            $table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onDelete('RESTRICT');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('packaging_items');
    }
}
