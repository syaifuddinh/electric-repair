<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMovementContainerStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('movement_container_statuses', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->smallInteger('order')->nullable(false)->default(0);
            $table->string('name', 20)->nullable(false);
            $table->timestamps();
        });

        DB::table('movement_container_statuses')
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
        Schema::dropIfExists('movement_container_statuses');
    }
}
