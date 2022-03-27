<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CostRouteTypeInCostType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cost_types', function (Blueprint $table) {
            $table->unsignedInteger('cost_route_type_id')->nullable(true);

            $table->foreign('cost_route_type_id')->references('id')->on('cost_route_types')->onDelete('set null');
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
            $table->dropForeign(['cost_route_type_id']);
            $table->dropColumn(['cost_route_type_id']);
        });
    }
}
