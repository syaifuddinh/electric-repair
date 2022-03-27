<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\JobOrder;

class JobOrderContainer
{
    /*
      Date : 12-02-2021
      Description : Menampilkan detail berdasarkan kontainer
      Developer : Didin
      Status : Create
    */
    public static function showByContainer($container_id) {
        $dt = DB::table('job_order_containers')
        ->whereContainerId($container_id);
        
        $dt = $dt->first();

        return $dt;
    }

    public static function store($container_id, $job_order_id) {
        $params = [];
        $params['job_order_id'] = $job_order_id;
        $params['container_id'] = $container_id;
        DB::table('job_order_containers')
        ->insert($params);
        JobOrder::countContainerPrice($job_order_id);
    }

    public static function index($job_order_id = null) {
        $dt = DB::table('job_order_containers');
        if($job_order_id) {
            $dt = $dt->where('job_order_id', $job_order_id);
        }
        $dt = $dt->get();

        return $dt;
    }

}
