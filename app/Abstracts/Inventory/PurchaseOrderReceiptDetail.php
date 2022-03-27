<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Inventory\WarehouseReceiptDetail;
use App\Abstracts\Inventory\Item;
use App\Abstracts\PurchaseOrderDetail;

class PurchaseOrderReceiptDetail
{
    protected static $table = 'purchase_order_receipt_details';


    public static function receiptQuery() {
        $dt = DB::table(self::$table);
        $dt = $dt->join('warehouse_receipt_details', 'warehouse_receipt_details.id', self::$table . '.warehouse_receipt_detail_id');
        $dt = $dt->groupBy(self::$table . '.purchase_order_detail_id');
        $dt = $dt->selectRaw(self::$table . ".purchase_order_detail_id, SUM(warehouse_receipt_details.qty) AS qty");

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Menghitung rata rata dari riwayat pembelian sebelumnya
      Developer : Didin
      Status : Create
    */
    public static function getAveragePrice($id) {
        $dt = self::show($id);
        $prev = DB::table(self::$table)->where("id", "<=", $id)->pluck("id");
        $avg = DB::table(self::$table)
        ->join("warehouse_receipt_details", "warehouse_receipt_details.id", self::$table . '.warehouse_receipt_detail_id')
        ->join("purchase_order_details", "purchase_order_details.id", self::$table . '.purchase_order_detail_id')
        ->whereIn(self::$table . ".id", $prev)
        ->where("purchase_order_details.item_id", $dt->item_id)
        ->avg(DB::raw("purchase_order_details.price"));

        $avg = number_format($avg, 2, '.', '');

        return $avg;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }


    /*
      Date : 29-08-2021
      Description : Menampilkan detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table);
        $dt = $dt->join("purchase_order_details", "purchase_order_details.id", self::$table . '.purchase_order_detail_id');
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(self::$table . '.*', "purchase_order_details.item_id");
        $dt = $dt->first();

        return $dt;
    }
    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }

    public static function clearByReceipt($warehouse_receipt_id) {
        if($warehouse_receipt_id) {
            $dt = DB::table(self::$table);
            $dt = $dt->whereRaw("warehouse_receipt_detail_id IN (SELECT id FROM warehouse_receipt_details WHERE header_id = $warehouse_receipt_id)");
            $dt->delete();
        }
    }

    /*
      Date : 05-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($warehouse_receipt_detail_id, $purchase_order_detail_id) {
        $params['warehouse_receipt_detail_id'] = $warehouse_receipt_detail_id;
        $params['purchase_order_detail_id'] = $purchase_order_detail_id;
        $dt = DB::table(self::$table);
        $id = $dt->insertGetId($params);
        self::setAveragePrice($id);

        return $id;
    }

    /*
      Date : 05-03-2021
      Description : Mengupdate harga beli
      Developer : Didin
      Status : Create
    */
    public static function setAveragePrice($id) {
        $avg = self::getAveragePrice($id);
        DB::table(self::$table)->whereId($id)->update([
            "average_price" => $avg
        ]);

        $dt = self::show($id);
        Item::setPurchasePrice($dt->item_id, $avg);

    }
}
