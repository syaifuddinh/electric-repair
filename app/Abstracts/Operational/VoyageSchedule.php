<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Operational\JobOrderContainer;
use App\Abstracts\Inventory\VoyageReceipt;
use App\Abstracts\JobOrder;

class VoyageSchedule
{
    protected static $table = 'voyage_schedules';

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);

        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('countries', 'countries.id', self::$table . '.countries_id');
        $dt = $dt->leftJoin('vessels', 'vessels.id', self::$table . '.vessel_id');
        $dt = $dt->leftJoin('ports AS pod', 'pod.id', self::$table . '.pod_id');
        $dt = $dt->leftJoin('ports AS pol', 'pol.id', self::$table . '.pol_id');
        $dt = $dt->leftJoin('users', 'users.id', self::$table . '.create_by');
        $dt = $dt->where(self::$table . '.id', $id);

        $dt = $dt->select(self::$table . '.*', 'countries.name AS country_name', 'vessels.name AS vessel_name', 'pod.name AS pod_name', 'pol.name AS pol_name', 'users.name AS creator_name');
        
        $dt = $dt->first();
        $dt->warehouse_receipt_id = VoyageReceipt::getWarehouseReceipt($id);

        return $dt;
    }

    /*
      Date : 07-03-2021
      Description : Menghapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        $jo_container = JobOrderContainer::showByContainer($id);
        if($jo_container) {
            $job_order_id = $jo_container->job_order_id;
        }
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
        JobOrder::countPrice($job_order_id);
    }
}
