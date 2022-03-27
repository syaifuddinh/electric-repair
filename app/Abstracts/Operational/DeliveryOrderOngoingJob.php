<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Setting\JobStatus;
use App\Abstracts\Operational\DeliveryOrderStatusLog;


class DeliveryOrderOngoingJob
{
    protected static $table = 'delivery_order_ongoing_jobs';

    public static function query($request = []) {
        $request = self::fetchFilter($request);
        $dt = DB::table(self::$table);
        $dt = $dt->join('job_orders', 'job_orders.id', self::$table . '.job_order_id');
        $dt = $dt->join('contacts AS customers', 'customers.id', 'job_orders.customer_id');

        if($request['delivery_order_driver_id']) {
            $dt = $dt->where(self::$table . '.delivery_order_driver_id', $request['delivery_order_driver_id']);
        }

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['delivery_order_driver_id'] = $args['delivery_order_driver_id'] ?? null;

        return $params;
    }

    public static function validate($id, $source = null) {
        $dt = DB::table(self::$table)
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 19-02-2021
      Description : Menampilkan detail surat jalan driver
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table);
        
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->first();

        return $dt;
    }

    public static function store($delivery_order_driver_id, $job_order_id) {
        DB::table(self::$table)
        ->insert([
            'delivery_order_driver_id' => $delivery_order_driver_id,
            'job_order_id' => $job_order_id
        ]);
    }
}
