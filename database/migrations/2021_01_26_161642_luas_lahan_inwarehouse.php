<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class LuasLahanInwarehouse extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->integer('luas_lahan')->nullable(false)->default(0);
            $table->integer('luas_bangunan')->nullable(false)->default(0);
            $table->integer('luas_gudang')->nullable(false)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['luas_lahan']);
            $table->dropColumn(['luas_bangunan']);
            $table->dropColumn(['luas_gudang']);
        });
    }
}
