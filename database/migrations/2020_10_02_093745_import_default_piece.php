<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ImportDefaultPiece extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $workOrder = DB::table('settings')
        ->whereSlug('work_order')
        ->first();
        $content = json_decode($workOrder->content);
        $settings = $content->settings;
        $settings[] = [
            'name' => 'Default Satuan',
            'type' => 'selectLookup',
            'table' => 'pieces',
            'placeholder' => 'Pilih Satuan',
            'slug' => 'default_piece_id',
            'value' => null
        ];
        $content->settings = $settings;
        DB::table('settings')
        ->whereSlug('work_order')
        ->update([
            'content' => json_encode($content)
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
    }
}
