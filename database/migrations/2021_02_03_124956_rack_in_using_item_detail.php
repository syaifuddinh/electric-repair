<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RackInUsingItemDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('using_item_details', function (Blueprint $table) {
            $table->unsignedInteger('warehouse_receipt_detail_id')->nullable(true)->index();
            $table->unsignedInteger('rack_id')->nullable(true)->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('using_item_details', function (Blueprint $table) {
            //
        });
    }
}
