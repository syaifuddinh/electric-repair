<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Model\delivery_order_driver AS M;
use App\Abstracts\Setting\JobStatus;


class DeliveryOrderStatusLog
{
    protected static $table = 'delivery_order_status_logs';

    public static function query($params = []) {
        $wr="1=1";

        $request = self::fetchFilter($params);

        $dt = DB::table(self::$table);
        $dt = $dt->join('job_statuses', 'job_statuses.id', self::$table . '.job_status_id');

        if($request['delivery_order_driver_id']) {
            $dt = $dt->where(self::$table . '.delivery_order_driver_id', $request['delivery_order_driver_id']);
        }

        $dt = $dt->orderBy(self::$table . '.created_at', "DESC");

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['delivery_order_driver_id'] = $args['delivery_order_driver_id'] ?? null;

        return $params;
    }

    public static function index($delivery_order_driver_id) {
        $dt = self::query(['delivery_order_driver_id' => $delivery_order_driver_id]);
        $dt = $dt->select(
            self::$table . '.created_at',
            self::$table . '.job_status_id',
            'job_statuses.name'
        );
        $dt = $dt->get();

        return $dt;
    }

    public static function getFirstIndex($delivery_order_driver_id) {
        $r = null;
        $dt = self::index($delivery_order_driver_id);
        if(count($dt) > 0) {
            $r = $dt[0];
        }

        return $r;
    } 

    public static function getLastIndex($delivery_order_driver_id) {
        $r = null;
        $dt = self::index($delivery_order_driver_id);
        if(count($dt) > 0) {
            $r = $dt[count($dt) - 1];
        }

        return $r;
    } 

    /*
      Date : 23-03-2021
      Description : Mengambil parameter untuk input data
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params['delivery_order_driver_id'] = $args['delivery_order_driver_id'] ?? null;
        $params['job_status_id'] = $args['job_status_id'] ?? null;
        $params['created_by'] = $args['created_by'] ?? auth()->id();
        $params['created_at'] = Carbon::now('Asia/Jakarta');

        return $params;
    }

    /*
      Date : 23-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params = []) {
        $params = self::fetch($params);
        $id = DB::table(self::$table)
        ->insertGetId($params);
    }

    /*
      Date : 23-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function storeAsStartedByDriver($params = []) {
        $params = self::fetch($params);
        $params['job_status_id'] = JobStatus::getStartedByDriverStatus();
        $id = DB::table(self::$table)
        ->insertGetId($params);
    }

    /*
      Date : 23-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function storeAsStartedByVendor($params = []) {
        $params = self::fetch($params);
        $params['job_status_id'] = JobStatus::getStartedByVendorStatus();
        $id = DB::table(self::$table)
        ->insertGetId($params);
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
        $job_statuses = JobStatus::query();
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.id', $id);


        $dt = $dt->first();

        return $dt;
    }

    public static function destroy($id) {
        self::validate($id);
        DB::beginTransaction();

        DB::table(self::$table)
        ->whereId($id)
        ->delete();

        DB::commit();
    }
}
