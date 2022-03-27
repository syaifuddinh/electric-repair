<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UseJobPacketInWorkOrder extends Migration
{
    protected $params = '{"settings":[{"name":"Menggunakan paket pekerjaan ?","slug":"use_job_packet","type":"boolean","value":0}]}';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exist = DB::table('settings')
        ->whereSlug('work_order')
        ->first();
        if($exist) {
            $params = json_decode($this->params);
            $settings = json_decode($exist->content);
            foreach($params->settings as $s) {
                array_push($settings->settings, $s);
            }
            $update = [];
            $update['content'] = json_encode($settings);
            DB::table('settings')
            ->whereSlug('work_order')
            ->update($update);
        } else {
            DB::table('settings')
            ->insert([
                'name' => 'Work Order',
                'slug' => 'work_order',
                'content' => $this->params
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
