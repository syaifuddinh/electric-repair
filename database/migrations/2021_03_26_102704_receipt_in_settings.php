<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ReceiptInSettings extends Migration
{

    protected $params = '{"settings":[{"name":"Default storage type ?","slug":"default_storage_type","type":"radio","value":"RACK", "options" : [{"id" : "RACK", "name" : "Bin location"}, {"id" : "HANDLING", "name" : "Handling Area"}] }]}';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('settings')
        ->insert([
            'name' => 'Good Receipt',
            'slug' => 'good_receipt',
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
        ->whereSlug('good_receipt')
        ->delete();
    }

}
