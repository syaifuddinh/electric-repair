<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RackInPackagingOldItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packaging_old_items', function (Blueprint $table) {
            $table->unsignedInteger("rack_id")->nullable(true)->index();

            $table->foreign("rack_id")->references("id")->on("racks")->onDelete("restrict");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packaging_old_items', function (Blueprint $table) {
            $table->dropForeign(['rack_id']);
            $table->dropColumn(['rack_id']);
        });
    }
}
