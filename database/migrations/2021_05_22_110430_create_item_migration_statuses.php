<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateItemMigrationStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('item_migration_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug', 30)->nullable(false)->index();
            $table->string('name', 100)->nullable(false)->index();
        });

        DB::table('item_migration_statuses')
        ->insert([
            [
                'id' => 1,
                'slug' => 'draft',
                'name' => 'Pengajuan'
            ],
            [
                'id' => 2,
                'slug' => 'itemOut',
                'name' => 'Item Out'
            ],
            [
                'id' => 3,
                'slug' => 'itemIn',
                'name' => 'Item Receipt'
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
        Schema::dropIfExists('item_migration_statuses');
    }
}
