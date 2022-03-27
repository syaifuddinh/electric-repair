<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DepoInSettings extends Migration
{
    protected $params = '{"settings":[{"name":"Ada kegiatan depo management ?","slug":"is_use_depo","type":"boolean","value":0}]}';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('settings')
        ->insert([
            'name' => 'General',
            'slug' => 'general',
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
        ->whereSlug('general')
        ->delete();
    }
}
