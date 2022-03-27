<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\WarehouseReceiptStatus;
use App\Abstracts\Inventory\PurchaseOrderReceiptDetail;

class PurchaseOrderDetail
{
    protected static $table = 'purchase_order_details';

    public static function clear($purchase_order_id) {
        DB::table(self::$table)
        ->whereHeaderId($purchase_order_id)
        ->delete();
    }

    /*
      Date : 29-08-2021
      Description : Meng-query purchase order
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $receiptQuery = PurchaseOrderReceiptDetail::receiptQuery();
        $receipts = DB::query()->fromSub($receiptQuery, 'receipts');

        $request = self::fetchFilter($params);
        $dt = DB::table(self::$table);
        $dt = $dt->join('items', 'items.id', self::$table . '.item_id');
        $dt = $dt->leftJoin('categories', 'categories.id', 'items.category_id');
        $dt = $dt->leftJoinSub($receipts, "receipts", function($query){
            $query->on("receipts.purchase_order_detail_id", self::$table . '.id');
        });

        if($request['header_id']) {
            $dt  = $dt->where(self::$table . '.header_id', $request['header_id']);
        }

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['header_id'] = $args['header_id'] ?? null;

        return $params;
    }

    /*
      Date : 29-08-2021
      Description : Mendapatkan harga total
      Developer : Didin
      Status : Create
    */
    public static function getTotalPrice($purchase_order_id) {
        $dt = self::query(['header_id' => $purchase_order_id])->sum('total');

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Meng-nampilkan daftar item
      Developer : Didin
      Status : Create
    */
    public static function index($header_id = null) {
        $dt = self::query(['header_id' => $header_id]);

        $dt = $dt->select(
            'purchase_order_details.id', 
            'purchase_order_details.qty', 
            'purchase_order_details.price', 
            'purchase_order_details.total', 
            'purchase_order_details.item_id', 
            'items.name AS item_name', 
            'categories.name AS category_name', 
            DB::raw('COALESCE(receipts.qty, 0) AS received_qty')
        );
        $dt = $dt->get();

        return $dt;
    }

    public static function storeMultiple($details, $purchase_order_id) {
        if(is_array($details)) {
            foreach($details as $detail) {
                self::store($detail, $purchase_order_id);
            }
        }
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan purchase order
      Developer : Didin
      Status : Create
    */
    public static function store($params = [], $purchase_order_id) {
        $args = [];
        $args['header_id'] = $purchase_order_id;
        $args['price'] = $params['price'] ?? null;
        $args['item_id'] = $params['item_id'] ?? null;
        $args['purchase_request_detail_id'] = $params['purchase_request_detail_id'] ?? null;
        $args['qty'] = $params['qty'] ?? 0;
        $args['receive'] = $params['receive'] ?? 0;
        $args['price'] = $params['price'] ?? 0;
        $args['total'] = self::getPriceTotal($args['qty'], $args['price']);
        $args['created_at'] = Carbon::now();

        $id = DB::table(self::$table)
        ->insertGetId($args);

        return $id;
    }

    public static function getPriceTotal($qty = 0, $price = 0) {
        return $qty * $price;
    }

    public static function getLimit($purchase_order_id, $item_id) {
        $dt = DB::table(self::$table);
        $dt = $dt->where('header_id', $purchase_order_id);  
        $dt = $dt->where('item_id', $item_id);  
        $qty = $dt->sum("qty");

        return $qty;
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail sales order
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        $dt = self::query();
        $dt = $dt->where(self::$table . '.id', $id)
          ->first();

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menghapus sales order
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        $exist = DB::table(self::$table)
        ->whereId($id)
        ->first();
        if($exist) {
            DB::table('purchase_order_details')
            ->whereId($id)
            ->delete();

        } else {
            throw new Exception('Data not found');
        }
    }

    public static function clearWarehouseReceipt($warehouse_receipt_id) {
        $dt = DB::table(self::$table);
    }
}
