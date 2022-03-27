<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChargeInTypeInCostTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cost_types', function (Blueprint $table) {
            $table->unsignedInteger('is_insurance')->nullable(false)->default(0);
            $table->integer('percentage')->nullable(false)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cost_types', function (Blueprint $table) {
            $table->dropColumn(['percentage']);
            $table->dropColumn(['is_insurance']);
        });
    }
}
