<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGateInContainerStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('gate_in_container_statuses')) {
            Schema::create('gate_in_container_statuses', function (Blueprint $table) {
                $table->increments('id');
                $table->smallInteger('order')->nullable(false)->default(0)->index();
                $table->string('name', 20)->nullable(false);
                $table->timestamps();
            });
        }

        DB::table('gate_in_container_statuses')
        ->insert([
            [
                'order' => 1,
                'name' => 'Draft'
            ],
            [
                'order' => 2,
                'name' => 'Approved'
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
        Schema::dropIfExists('gate_in_container_statuses');
    }
}
