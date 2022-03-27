<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class JadwalKapalInReceiptTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('receipt_types')
        ->insert([
            'code' => 'r10',
            'name' => 'Voyage scheduled / jadwal kapal'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('receipt_types')
        ->whereCode('r10')
        ->delete();
    }
}
