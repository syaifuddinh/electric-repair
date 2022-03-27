<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AutoUpdateStatusInSettingJobOrder extends Migration
{
    protected $params = '{"settings":[{"name":"Update status otomatis ?","slug":"auto_update_status","type":"boolean","value":0}]}';
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exist = DB::table('settings')
        ->whereSlug('job_order')
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
            ->whereSlug('job_order')
            ->update($update);
        } else {
            DB::table('settings')
            ->insert([
                'name' => 'Job Order',
                'slug' => 'job_order',
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
        DB::table('settings')
        ->whereSlug('job_order')
        ->delete();
    }
}
