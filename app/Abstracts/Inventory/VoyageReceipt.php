<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\WarehouseReceipt;

class VoyageReceipt
{
    protected static $table = 'voyage_receipts';
    protected static $primary_id = 'voyage_schedule_id';

    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */   
    public static function store($voyage_schedule_id, $warehouse_receipt_id) {
        if($warehouse_receipt_id && $voyage_schedule_id) {
            DB::table(self::$table)
            ->insert([
                'voyage_schedule_id' => $voyage_schedule_id,
                'warehouse_receipt_id' => $warehouse_receipt_id
            ]);
        }
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi apakah map ini sudah pernah ditempatkan pada 1 bin location / rack
      Developer : Didin
      Status : Create
    */  
    public static function validateIsExist($voyage_schedule_id, $warehouse_receipt_id) {
        $res = false;
        $exist = DB::table(self::$table)
        ->where(self::$table . '.voyage_schedule_id', '!=', $voyage_schedule_id)
        ->where(self::$table . '.warehouse_receipt_id', '=', $warehouse_receipt_id)
        ->count(self::$table . '.id');        

        if($exist > 0) {
            throw new Exception('Voyage schedule has another receipt');
        }
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi apakah rak ini sudah pernah sudah punya  bin location / rack atau belum
      Developer : Didin
      Status : Create
    */  
    public static function validate($voyage_schedule_id) {
        $res = false;
        $exist = DB::table(self::$table)
        ->where(self::$table . '.voyage_schedule_id', $voyage_schedule_id)
        ->count(self::$table . '.id');

        if($exist > 0) {
            $res = true;
        }

        return $res;
    }

    public static function storeReceipt($params = [], $voyage_schedule_id) {
        $params['receipt_type_id'] = null;
        $params['receipt_type_code'] = 'r10';
        $warehouse_receipt_id = WarehouseReceipt::store($params);
        self::store($voyage_schedule_id, $warehouse_receipt_id);
    }

    public static function getWarehouseReceipt($voyage_schedule_id) {
        $r = null;
        $dt = DB::table(self::$table);
        $dt = $dt->whereVoyageScheduleId($voyage_schedule_id);
        $dt = $dt->first();
        if($dt) {
            $r = $dt->warehouse_receipt_id;
        }

        return $r;
    }


    /*
      Date : 09-05-2021
      Description : Mendapatkan ID jadwal kapal
      Developer : Didin
      Status : Create
    */
    public static function getPrimaryId($warehouse_receipt_id) {
        $r = null;
        $dt = DB::table(self::$table);
        $dt = $dt->whereWarehouseReceiptId($warehouse_receipt_id);
        $dt = $dt->first();
        if($dt) {
            $dt = (array) $dt;
            $r = $dt[self::$primary_id];
        }

        return $r;
    }
}
