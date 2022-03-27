<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IsDangerousGoodsInRacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('racks', function (Blueprint $table) {
            $table->smallInteger('is_dangerous_good')->nullable(false)->default(0)->index();
            $table->smallInteger('is_fast_moving')->nullable(false)->default(0)->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('racks', function (Blueprint $table) {
            $table->dropColumn(['is_dangerous_good']);
            $table->dropColumn(['is_fast_moving']);
        });
    }
}
