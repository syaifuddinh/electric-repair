<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePackagingNewItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packaging_new_items', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->unsignedInteger('packaging_id')->nullable(false)->index();
            $table->integer('qty')->nullable(false)->default(0);
            $table->unsignedInteger('item_id')->nullable(false)->index();
            $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(true)->index();
            $table->unsignedInteger('approved_stock_transaction_id')->nullable(true)->index();
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('RESTRICT');
            $table->foreign('warehouse_receipt_detail_id')->references('id')->on('warehouse_receipt_details')->onDelete('RESTRICT');
            $table->foreign('approved_stock_transaction_id')->references('id')->on('stock_transactions')->onDelete('RESTRICT');
        
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('packaging_new_items');
    }
}
