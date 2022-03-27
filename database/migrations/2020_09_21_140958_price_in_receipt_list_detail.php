<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PriceInReceiptListDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('receipt_list_details', function (Blueprint $table) {
            $table->double('price')->nullable(false)->default(0);
            $table->double('total_price')->nullable(false)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('receipt_list_details', function (Blueprint $table) {
            $table->dropColumn(['price', 'total_price']);
        });
    }
}
