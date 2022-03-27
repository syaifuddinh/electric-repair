<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\PurchaseOrder;
use App\Abstracts\Inventory\Item;
use App\Abstracts\Inventory\PurchaseRequestDetail;

class PurchaseRequest
{
    protected static $table = 'purchase_requests';
    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $dt = DB::table(self::$table);
        $dt = $dt->join('contacts AS suppliers', 'suppliers.id', self::$table . '.supplier_id');
        $dt = $dt->join('warehouses', 'warehouses.id', self::$table . '.warehouse_id');
        $dt = $dt->join('companies', 'companies.id', self::$table . '.company_id');
        $dt = $dt->join('users AS creators', 'creators.id', self::$table . '.create_by');

        $params = self::fetchFilter($params);
        if($params['company_id']) {
            $dt = $dt->where(self::$table . '.company_id', $params['company_id']);
        }

        if($params['start_date']) {
            $dt = $dt->where(self::$table . '.date_request', '>=', $params['start_date']);
        }

        if($params['end_date']) {
            $dt = $dt->where(self::$table . '.date_request', '<=', $params['end_date']);
        }

        if($params['is_pallet']) {
            $dt = $dt->whereRaw(self::$table . '.id IN (SELECT purchase_request_details.header_id FROM purchase_request_details JOIN items ON items.id = purchase_request_details.item_id JOIN categories ON categories.id = items.category_id LEFT JOIN categories AS parents ON parents.id = categories.parent_id WHERE categories.is_pallet = 1 OR parents.is_pallet = 1)');
        }

        if($params['is_merchandise']) {
            $dt = $dt->whereRaw(self::$table . '.id IN (SELECT purchase_request_details.header_id FROM purchase_request_details JOIN items ON items.id = purchase_request_details.item_id WHERE is_merchandise = 1)');
        }

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan daftar nama item condition
      Developer : Didin
      Status : Create
    */
    public static function index() {
        $dt = self::query();
        $dt = $dt->select('warehouses.id', 'warehouses.name');
        $dt = $dt->get();

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter untuk filter
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($args = []) {
        $params = [];
        $params['is_pallet'] = $args['is_pallet'] ?? null;
        if($params['is_pallet'] && $params['is_pallet'] > 1) {
            $params['is_pallet'] = 1;
        }

        $params['is_merchandise'] = $args['is_merchandise'] ?? null;
        if($params['is_merchandise'] && $params['is_merchandise'] > 1) {
            $params['is_merchandise'] = 1;
        }

        $params['company_id'] = $args['company_id'] ?? null;

        $params['start_date'] = $args['start_date'] ?? null;
        if($params['start_date']) {
            $params['start_date'] = Carbon::parse($params['start_date'])->format('Y-m-d');
        }
        
        $params['end_date'] = $args['end_date'] ?? null;
        if($params['end_date']) {
            $params['end_date'] = Carbon::parse($params['end_date'])->format('Y-m-d');
        }

        return $params;
    }
    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['name'] = $args['name'] ?? null;
        $params['description'] = $args['description'] ?? null;
        return $params;
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
      Description : Menampilkan detail kategori barang
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = self::query();
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(
            self::$table . '.*', 
            'warehouses.name AS warehouse_name',
            'companies.name AS company_name',
            'suppliers.name AS supplier_name',
            'creators.name AS creator_name'
        );
        $dt = $dt->first();

        return $dt;
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params) {
        $insert = self::fetch($params);
        $id = DB::table(self::$table)->insertGetId($insert);

        return $id;
    }
    
    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params, $id) {
        self::validate($id);
        $update = self::fetch($params);
        DB::table('warehouses')
        ->whereId($id)
        ->update($update);
    }
    
    /*
      Date : 29-08-2021
      Description : Update data map 
      Developer : Didin
      Status : Create
    */
    public static function updateMap($id, $row, $column, $level) {
        self::validate($id);
        DB::table('warehouses')
        ->whereId($id)
        ->update([
            'row' => $row,
            'column' => $column,
            'level' => $level
        ]);
    }

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table('warehouses')
        ->whereId($id)
        ->delete();
    }

    /*
      Date : 14-03-2021
      Description : Membuat purchase order
      Developer : Didin
      Status : Create
    */
    public static function createPurchaseOrder($payment_type, $purchase_date, $detail = null, $id) {
        $dt = self::show($id);
        $params = [];
        $params['purchase_request_id'] = $id;
        $params['payment_type'] = $payment_type;
        $params['company_id'] = $dt->company_id;
        $params['warehouse_id'] = $dt->warehouse_id;
        $params['supplier_id'] = $dt->supplier_id;
        $params['po_date'] = $purchase_date;
        if(is_array($detail)) {
            $detail = collect($detail);
            $detail = $detail->filter(function($v){
                return ($v['id'] ?? null);
            });

            $params['detail'] = $detail->map(function($dt){
                $params = [];
                $v = PurchaseRequestDetail::show($dt['id']);
                $params['item_id'] = $v->item_id; 
                $params['qty'] = $v->qty_approve; 
                $params['price'] = $dt['price'] ?? 0; 
                $params['purchase_request_detail_id'] = $v->id; 


                return $params;
            })->toArray();
        }
        $purchase_order_id = PurchaseOrder::store($params);
        $status = self::getPurchaseOrderCreatedStatus();
        PurchaseOrder::approve($purchase_order_id);
        DB::table(self::$table)
        ->whereId($id)
        ->update([
            "status" => $status
        ]);
    }

    public static function getPurchaseOrderCreatedStatus() {
        return 3;
    }
}
