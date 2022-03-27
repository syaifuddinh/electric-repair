<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SlugInImposition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('impositions', function (Blueprint $table) {
            $table->string('slug', 20)->nullable(false)->index();
        });

        DB::table('impositions')
        ->insert([
            [
                'id' => 1,
                'slug' => 'volume',
                'name' => 'Kubikasi'
            ],
            [
                'id' => 2,
                'slug' => 'tonnage',
                'name' => 'Tonase'
            ],
            [
                'id' => 3,
                'slug' => 'item',
                'name' => 'Item'
            ],
            [
                'id' => 4,
                'slug' => 'borongan',
                'name' => 'Borongan'
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
        DB::table('impositions')->delete();
        Schema::table('impositions', function (Blueprint $table) {
            $table->dropColumn(['slug']);
        });
    }
}
