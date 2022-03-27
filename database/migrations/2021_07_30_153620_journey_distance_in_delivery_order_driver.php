<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class JourneyDistanceInDeliveryOrderDriver extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('delivery_order_drivers', function (Blueprint $table) {
            $table->double('journey_distance', 8, 3)->nullable(false)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('delivery_order_drivers', function (Blueprint $table) {
            $table->dropColumn(['journey_distance']);
        });
    }
}
