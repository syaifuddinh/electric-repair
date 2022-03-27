<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class StatusInPurchaseOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedInteger('status')->nullable(true)->index();

            $table->foreign('status')->references('id')->on('purchase_order_statuses')->onDelete('set null');
        });

        $approved = DB::table('purchase_order_statuses')
        ->whereSlug('approved')
        ->first();
        if($approved) {
            DB::table('purchase_orders')
            ->update([
                'status' => $approved->id
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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['status']);
            $table->dropColumn(['status']);
        });
    }
}
