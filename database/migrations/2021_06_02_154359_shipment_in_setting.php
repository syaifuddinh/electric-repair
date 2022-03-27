<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ShipmentInSetting extends Migration
{
    protected $params = '{"settings":[{"name":"Apakah pengisian barang dimulai dari NOL ?","slug":"is_zero_when_update_item","type":"boolean","value":0}]}';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('settings')
        ->insert([
            'name' => 'Shipment / Manifest',
            'slug' => 'shipment',
            'content' => $this->params
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('settings')
        ->whereSlug('shipment')
        ->delete();
    }
}
