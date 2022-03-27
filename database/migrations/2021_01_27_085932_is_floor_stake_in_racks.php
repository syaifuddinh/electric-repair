<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IsFloorStakeInRacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('racks', function (Blueprint $table) {
            $table->smallInteger('is_floor_stake')->default(0)->nullable(false);
            $table->integer('luas')->default(0)->nullable(false);
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
            $table->dropColumn(['is_floor_stake']);
            $table->dropColumn(['luas']);
        });
    }
}
