<?php

namespace App\Abstracts\Sales;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\WarehouseReceipt;
use App\Abstracts\Inventory\WarehouseReceiptDetail;
use App\Abstracts\Sales\SalesOrderReturnDetail;
use App\Abstracts\Sales\SalesOrderReturn;

class SalesOrderReturnReceipt
{
    protected static $table = 'sales_order_return_receipts';
    protected static $primary_id = 'sales_order_return_id';

    public static function query() {
        $dt = DB::table(self::$table);
        $dt = $dt->join('warehouse_receipt_details', 'warehouse_receipt_details.id', self::$table . '.warehouse_receipt_detail_id');

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan map
      Developer : Didin
      Status : Create
    */   
    public static function store($sales_order_return_detail_id, $warehouse_receipt_detail_id) {
        if($warehouse_receipt_detail_id && $sales_order_return_detail_id) {
            $wd = WarehouseReceiptDetail::show($warehouse_receipt_detail_id);
            $sor = SalesOrderReturnDetail::show($sales_order_return_detail_id);
            DB::table(self::$table)
            ->insert([
                'sales_order_return_id' => $sor->header_id,
                'warehouse_receipt_id' => $wd->header_id,
                'sales_order_return_detail_id' => $sales_order_return_detail_id,
                'warehouse_receipt_detail_id' => $warehouse_receipt_detail_id
            ]);
        }
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi apakah map ini sudah pernah ditempatkan pada 1 bin location / rack
      Developer : Didin
      Status : Create
    */  
    public static function validateIsExist($sales_order_return_id, $warehouse_receipt_id) {
        $res = false;
        $exist = DB::table(self::$table)
        ->where(self::$table . '.sales_order_return_id', '!=', $sales_order_return_id)
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
    public static function validate($sales_order_return_id) {
        $res = false;
        $exist = DB::table(self::$table)
        ->where(self::$table . '.sales_order_return_id', $sales_order_return_id)
        ->count(self::$table . '.id');

        if($exist > 0) {
            $res = true;
        }

        return $res;
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan penerimaan barang
      Developer : Didin
      Status : Create
    */
    public static function storeReceipt($params = [], $sales_order_return_id) {
        $params['receipt_type_id'] = null;
        $params['receipt_type_code'] = 'r07';
        $warehouse_receipt_id = WarehouseReceipt::store($params);
        $items = WarehouseReceiptDetail::index($warehouse_receipt_id);
        $items->each(function($v) use ($sales_order_return_id){
            $item = SalesOrderReturnDetail::showByItem($sales_order_return_id, $v->item_id);

            if($item) {
                $sales_order_return_detail_id = $item->id;
                $warehouse_receipt_detail_id = $v->id;
                self::store($sales_order_return_detail_id, $warehouse_receipt_detail_id);
            }
        });

        SalesOrderReturn::validateLimit($sales_order_return_id);
    }

    public static function getWarehouseReceipt($sales_order_return_id) {
        $r = null;
        $dt = DB::table(self::$table);
        $dt = $dt->whereItemMigrationId($sales_order_return_id);
        $dt = $dt->first();
        if($dt) {
            $r = $dt->warehouse_receipt_id;
        }

        return $r;
    }

    /*
      Date : 09-05-2021
      Description : Mendapatkan ID mutasi transfer
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
