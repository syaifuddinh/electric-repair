<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PaymentTypeInPurchaseOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->integer('payment_type')
            ->nullable(false)
            ->default(1)
            ->index();
            $table->unsignedInteger('cash_transaction_id')
            ->nullable(true);
            $table->unsignedInteger('payable_id')
            ->nullable(true);
            $table->unsignedInteger('journal_id')
            ->nullable(true);

            $table->foreign('cash_transaction_id')->references('id')->on('cash_transactions')->onDelete('set null');
            $table->foreign('payable_id')->references('id')->on('payables')->onDelete('set null');
            $table->foreign('journal_id')->references('id')->on('journals')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['payable_id']);
            $table->dropForeign(['cash_transaction_id']);
            $table->dropForeign(['journal_id']);
            $table->dropColumn(['payment_type', 'payable_id', 'cash_transaction_id', 'journal_id']);
        });
    }
}
