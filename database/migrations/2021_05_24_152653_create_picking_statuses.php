<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePickingStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('picking_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug')->nullable(false)->index();
            $table->string('name')->nullable(false);
        });

        DB::table('picking_statuses')
        ->insert([
            [
                'slug' => 'draft',
                'name' => 'Draft'
            ],
            [
                'slug' => 'approved',
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
        Schema::dropIfExists('picking_statuses');
    }
}
