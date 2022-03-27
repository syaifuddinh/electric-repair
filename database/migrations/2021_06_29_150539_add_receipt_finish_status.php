<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddReceiptFinishStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('purchase_order_statuses')
        ->insert([
            'slug' => 'finished',
            'name' => 'Receipt finished'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('purchase_order_statuses')
        ->whereSlug('finished')
        ->delete();
    }
}
