<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AirfreightInJobOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->string('agent_name', 100)->nullable(true);
            $table->string('awb_number', 100)->nullable(true);
            $table->string('flight_code', 100)->nullable(true);
            $table->string('flight_route', 100)->nullable(true);
            $table->date('flight_date')->nullable(true);
            $table->date('cargo_ready_date')->nullable(true);
            $table->string('house_awb', 100)->nullable(true);
            $table->string('hs_code', 100)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('job_orders', function (Blueprint $table) {
            $table->dropColumn(['agent_name', 'awb_number', 'flight_code', 'flight_date', 'cargo_ready_date', 'house_awb', 'hs_code', 'flight_route']);
        });
    }
}
