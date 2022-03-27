<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCostRouteTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cost_route_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 30)->nullable(false)->index();
            $table->string('name', 80)->nullable(false)->index();
        });

        DB::table('cost_route_types')
        ->insert([
            [
                'slug' => 'requested',
                'name' => 'Rencana pengiriman (KM Rute)'
            ],
            [
                'slug' => 'real',
                'name' => 'Realisasi pengiriman'
            ],
            [
                'slug' => 'manual',
                'name' => 'Manual'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cost_route_types');
    }
}
