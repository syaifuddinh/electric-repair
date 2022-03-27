<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ParentIdInRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('roles', 'created_at')) {

            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn(['created_at']);
            });
        }

        if(Schema::hasColumn('roles', 'updated_at')) {

            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn(['updated_at']);
            });
        }
        
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedInteger('parent_id')->nullable(true)->index();

            $table->foreign('parent_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['parent_id']);
        });
    }
}
