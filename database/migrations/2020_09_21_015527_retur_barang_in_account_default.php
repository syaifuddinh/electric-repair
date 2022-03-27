<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReturBarangInAccountDefault extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('account_defaults', function (Blueprint $table) {
            $table->unsignedInteger('retur_barang')
            ->nullable(true);

            $table->foreign('retur_barang')->references('id')->on('account_defaults')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('account_defaults', function (Blueprint $table) {
            $table->dropForeign(['retur_barang']);
            $table->dropColumn(['retur_barang']);
        });
    }
}
