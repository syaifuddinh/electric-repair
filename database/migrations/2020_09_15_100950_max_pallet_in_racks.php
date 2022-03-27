<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MaxPalletInRacks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('racks', function(Blueprint $table)
        {
            $table->integer('max_pallet')
            ->nullable(false)
            ->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('racks', function(Blueprint $table)
        {
            $table->dropColumn(['max_pallet']);
        });
    }
}
