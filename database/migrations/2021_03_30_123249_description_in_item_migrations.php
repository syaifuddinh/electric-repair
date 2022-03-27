<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DescriptionInItemMigrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('item_conditions', 'description')) {
            Schema::table('item_conditions', function (Blueprint $table) {
                $table->text('description')->nullable(true);
            });
        }
    }

    /**
     * Reverse the conditions.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('item_conditions', function (Blueprint $table) {
            $table->dropColumn(['description']);
        });
    }
}
