<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PickingInSettings extends Migration
{
    protected $params = '{"settings":[
        {"name":"Urutan bin location ketika pengambilan barang ?","slug":"bin_location_order","type":"radio","value":"FRONT", "options" : [{"id" : "FRONT", "name" : "Dari depan"}, {"id" : "BEHIND", "name" : "Dari belakang"}] },
        {"name":"Urutan level bin location ketika pengambilan barang ?","slug":"bin_location_level_order","type":"radio","value":"BOTTOM", "options" : [{"id" : "TOP", "name" : "Dari atas"}, {"id" : "BOTTOM", "name" : "Dari bawah"}] }
    ]}';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('settings')
        ->insert([
            'name' => 'Picking',
            'slug' => 'picking',
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
        ->whereSlug('picking')
        ->delete();
    }
}
