<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateReturStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('retur_statuses', function (Blueprint $table) {
            $table->unsignedInteger('id')->unique();
            $table->string('name', 20)->nullable(false);
        });

        DB::table('retur_statuses')
        ->insert([
            [
                'id' => 1,
                'name' => 'Draft'
            ],
            [
                'id' => 2,
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
        Schema::dropIfExists('retur_statuses');
    }
}
