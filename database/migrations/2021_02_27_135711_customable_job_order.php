<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CustomableJobOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('type_transactions')
        ->whereSlug('jobOrder')
        ->update([
            'is_customable_field' => 1
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('type_transactions')
        ->whereSlug('jobOrder')
        ->update([
            'is_customable_field' => 0
        ]);
    }
}
