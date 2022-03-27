<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemMigrationReceipts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_migration_receipts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('item_migration_id')->nullable(false)->index();
            $table->unsignedInteger('warehouse_receipt_id')->nullable(false)->index();

            $table->foreign('item_migration_id')->references('id')->on('item_migrations')->onDelete('cascade');
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
        Schema::dropIfExists('item_migration_receipts');
    }
}
