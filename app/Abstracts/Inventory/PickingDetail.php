<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\StockTransaction;
use App\Abstracts\Inventory\Picking;
use App\Abstracts\Inventory\Item;
use App\Abstracts\Inventory\WarehouseReceiptDetail;
use App\Abstracts\Rack;

class PickingDetail
{
    protected static $table = 'picking_details';

    /*
      Date : 05-03-2021
      Description : Menampilkan item pada picking
      Developer : Didin
      Status : Create
    */
    public static function index($picking_id = null) {
        $dt = DB::table(self::$table);
        if($picking_id) {
            $dt = $dt->where(self::$table . '.header_id', $picking_id);
        }
        $dt = $dt->get();
        
        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('picking_details');

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = self::query();
        $dt = $dt->where('picking_details.id', $id);
        $dt = $dt->select('picking_details.*');
        $dt = $dt->first();
        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi picking detail
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('picking_details')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Picking detail not found');
        }
    }

    /*
      Date : 23-03-2021
      Description : Mengambil parameter untuk input data
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params['rack_id'] = $args['rack_id'] ?? null;
        $params['qty'] = $args['qty'] ?? 0;
        $params['warehouse_receipt_detail_id'] = $args['warehouse_receipt_detail_id'] ?? null;

        if(!$params['rack_id']) {
            throw new Exception('Rack / bin location is required');
        }

        if(!$params['warehouse_receipt_detail_id']) {
            throw new Exception('Warehouse receipt detail location is required');
        } else {
            $wrd = WarehouseReceiptDetail::show($params['warehouse_receipt_detail_id']);
            $params['item_id'] = $wrd->item_id;
        }

        return $params;
    }

    /*
      Date : 23-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params = [], $id) {
        $params = self::fetch($params);
        $params['header_id'] = $id;
        $id = DB::table(self::$table)
        ->insertGetId($params);

        self::doRequestOutbound($id);
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function storeMultiple($details, $picking_id) {
        if(is_array($details)) {
            self::clear($picking_id);
            foreach($details as $detail) {
                $detail = (array) $detail;
                self::store($detail, $picking_id);
            }
        }
    }

    /*
      Date : 23-03-2021
      Description : Memasukkan perencanaan ke dalam stock transaction
      Developer : Didin
      Status : Create
    */
    public static function doRequestOutbound($id) {
        $dt = self::show($id);
        $picking = Picking::show($dt->header_id);
        $item = Item::show($dt->item_id);
        $stock['description'] = 'Telah direncanakan picking ' . $item->name . ' pada transaksi ' . $picking->code;
        $stock['date_transaction'] = $picking->date_transaction;
        $stock['qty_keluar'] = $dt->qty;
        $stock['warehouse_receipt_detail_id'] = $dt->warehouse_receipt_detail_id;
        $stock['type_transaction'] = 'picking';

        $stock['rack_id'] = $dt->rack_id;
        $request_stock_transaction_id = StockTransaction::doRequestOutbound($stock);
        self::query()->where('picking_details.id', $id)->update([
            'requested_stock_transaction_id' => $request_stock_transaction_id
        ]);
    }

    public static function doRequestOutboundMultiple($picking_id) {
        $items = self::index($picking_id);
        $items->each(function($v){
            self::doRequestOutbound($v->id);
        });
    }

    /*
      Date : 14-03-2021
      Description : Menghapus stok
      Developer : Didin
      Status : Create
    */
    public static function clearStock($picking_id) {
        $items = self::index($picking_id)->where('requested_stock_transaction_id', '!=', null)->pluck('requested_stock_transaction_id')->toArray();
        DB::table(self::$table)->whereHeaderId($picking_id)->update([
            'requested_stock_transaction_id' => null
        ]);
        StockTransaction::destroyMultiple($items);
    }

    public static function resetRequestOutbound($picking_id) {
        self::clearStock($picking_id);
        self::doRequestOutboundMultiple($picking_id);
    }

    /*
      Date : 05-03-2021
      Description : hapus semua data berdasarkan ID Picking
      Developer : Didin
      Status : Create
    */
    public static function clear($picking_id) {
        Picking::validateIsApproved($picking_id);
        self::clearStock($picking_id);
        DB::table(self::$table)->whereHeaderId($picking_id)->delete();
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan jumlah barang pada picking area
      Developer : Didin
      Status : Create
    */
    public static function getAvailableQty($id) {
        $dt = self::show($id);
        $rack = Rack::show($dt->rack_id);
        $warehouse_id = $rack->warehouse_id;
        $picking_area_id = Rack::getPickingArea($warehouse_id);
        $qty = StockTransaction::getAvailibity($dt->warehouse_receipt_detail_id, $picking_area_id, 0);

        return $qty;


    }
}
