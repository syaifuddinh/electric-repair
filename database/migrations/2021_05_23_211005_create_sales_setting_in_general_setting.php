<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSalesSettingInGeneralSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $dt = DB::table('settings')
        ->whereSlug('general')
        ->first();

        if($dt) {
            $json = json_decode($dt->content);
            $settings = $json->settings;

            $val = [];
            $val["name"] = "Ada kegiatan penjualan barang ?";
            $val["slug"] = "is_use_sales";
            $val["type"] = "boolean";
            $val["value"] = 0 ;
            $settings[] = $val;
            $json->settings = $settings;
            $content = json_encode($json);

            DB::table('settings')
            ->whereSlug('general')
            ->update([
                'content' => $content
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
