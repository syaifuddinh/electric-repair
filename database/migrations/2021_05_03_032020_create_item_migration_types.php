<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemMigrationTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_migration_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string("slug", 20)->nullable(false)->index();
        });

        DB::table('item_migration_types')
        ->insert([
            [
                'slug' => 'putaway'
            ],
            [
                'slug' => 'itemMigration'
            ]
        ]);
        Schema::table('item_migrations', function (Blueprint $table) {
            $table->unsignedInteger("item_migration_type_id")->nullable(true)->index();
            $table->foreign('item_migration_type_id')->references('id')->on('item_migration_types')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('item_migration_types');
        Schema::table('item_migrations', function (Blueprint $table) {
            $table->dropForeign(['item_migration_type_id']);
            $table->dropColumn(['item_migration_type_id']);
        });
    }
}
