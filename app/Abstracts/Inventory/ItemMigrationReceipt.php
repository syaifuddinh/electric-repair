<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\WarehouseReceipt;
use App\Abstracts\Inventory\WarehouseReceiptDetail;
use App\Abstracts\Inventory\ItemMigration;

class ItemMigrationReceipt
{
    protected static $table = 'item_migration_receipts';
    protected static $primary_id = 'item_migration_id';

    /*
      Date : 29-08-2021
      Description : Meng-query data
      Developer : Didin
      Status : Create
    */
    public static function query($request = []) {
        $request = self::fetchFilter($request);
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin("warehouse_receipts", 'warehouse_receipts.id', self::$table . '.warehouse_receipt_id');
        $dt = $dt->leftJoin("warehouse_receipt_details", 'warehouse_receipt_details.header_id', 'warehouse_receipts.id');

        if($request['item_migration_id']) {
            $dt = $dt->where(self::$table . '.item_migration_id', $request['item_migration_id']);
        }


        return $dt;
    }

    public static function receiptQuery($request = []) {
        $dt = self::query($request);
        $dt = $dt->groupBy('warehouse_receipt_details.item_id');
        $dt = $dt->select(
            'warehouse_receipt_details.item_id',
            DB::raw('SUM(warehouse_receipt_details.qty) AS qty')
        );

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['item_migration_id'] = $args['item_migration_id'] ?? null;

        return $params;
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan map
      Developer : Didin
      Status : Create
    */   
    public static function store($item_migration_id, $warehouse_receipt_id) {
        if($warehouse_receipt_id && $item_migration_id) {
            DB::table(self::$table)
            ->insert([
                'item_migration_id' => $item_migration_id,
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
    public static function validateIsExist($item_migration_id, $warehouse_receipt_id) {
        $res = false;
        $exist = DB::table(self::$table)
        ->where(self::$table . '.item_migration_id', '!=', $item_migration_id)
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
    public static function validate($item_migration_id) {
        $res = false;
        $exist = DB::table(self::$table)
        ->where(self::$table . '.item_migration_id', $item_migration_id)
        ->count(self::$table . '.id');

        if($exist > 0) {
            $res = true;
        }

        return $res;
    }

    /*
      Date : 02-06-2021
      Description : Menyimpan penerimaan barang
      Developer : Didin
      Status : Create
    */
    public static function storeReceipt($params = [], $item_migration_id) {
        $params['receipt_type_id'] = null;
        $params['receipt_type_code'] = 'r03';
        $warehouse_receipt_id = WarehouseReceipt::store($params);
        self::store($item_migration_id, $warehouse_receipt_id);
        WarehouseReceiptDetail::checkTransferMutation($warehouse_receipt_id);
        ItemMigration::finishReceipt($item_migration_id);
    }

    public static function getWarehouseReceipt($item_migration_id) {
        $r = null;
        $dt = DB::table(self::$table);
        $dt = $dt->whereItemMigrationId($item_migration_id);
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

    /*
      Date : 02-06-2021
      Description : Mendapatkan total barang yang telah diterima
      Developer : Didin
      Status : Create
    */
    public static function getReceived($warehouse_receipt_id, $item_id = null) {
        $receipt = DB::table(self::$table)->whereWarehouseReceiptId($warehouse_receipt_id)->first();

        $r = 0;

        if($receipt) {

            $dt = DB::table(self::$table);
            $dt = $dt->join('warehouse_receipts', 'warehouse_receipts.id', self::$table . '.warehouse_receipt_id');
            $dt = $dt->join('warehouse_receipt_details', 'warehouse_receipt_details.header_id',  'warehouse_receipts.id');

            $dt->where(self::$table . '.item_migration_id', $receipt->item_migration_id);

            if($item_id) {
                $dt->where('warehouse_receipt_details.item_id', $item_id);
            }

            $r = $dt->sum('warehouse_receipt_details.qty');
        }


        return $r;
    }

    /*
      Date : 02-06-2021
      Description : Mendapatkan total barang yang telah diterima
      Developer : Didin
      Status : Create
    */
    public static function getRequested($warehouse_receipt_id, $item_id = null) {

        $r = 0;

        $receipt = DB::table(self::$table)->whereWarehouseReceiptId($warehouse_receipt_id)->first();

        if($receipt) {
            $params['header_id'] = $receipt->item_migration_id;
            if($item_id) {
                $params['item_id'] = $item_id;
            }

            $dt = ItemMigrationDetail::query($params);

            $r = $dt->sum('item_migration_details.qty');
        }

        return $r;
    }
}
