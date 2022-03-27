<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Inventory\Packaging;
use App\Abstracts\Inventory\StockTransaction;
use App\Abstracts\Inventory\Item;

class PackagingOldItem
{
    protected static $table = 'packaging_old_items';

    /*
      Date : 05-03-2021
      Description : Menghapus semua data
      Developer : Didin
      Status : Create
    */
    public static function clear($packaging_id) {
        Packaging::validate($packaging_id);
        $dt = self::query();
        $dt = $dt->where('packaging_old_items.packaging_id', $packaging_id);
        $dt->delete();
    }

    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('packaging_old_items');
        $dt = $dt->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'packaging_old_items.warehouse_receipt_detail_id');
        $dt = $dt->leftJoin('items', 'items.id', 'warehouse_receipt_details.item_id');
        $dt = $dt->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id');

        return $dt;
    }
    /*
      Date : 05-03-2021
      Description : Menampilkan daftar item pada inspeksi
      Developer : Didin
      Status : Create
    */
    public static function index($packaging_id) {
        Packaging::validate($packaging_id);
        $dt = self::query();
        $dt = $dt->where('packaging_old_items.packaging_id', $packaging_id);
        $dt = $dt->select('packaging_old_items.id', 'packaging_old_items.rack_id', 'packaging_old_items.requested_stock_transaction_id', 'packaging_old_items.packaging_id', 'items.name AS item_name', 'packaging_old_items.qty', 'warehouse_receipts.code AS warehouse_receipt_code', 'packaging_old_items.warehouse_receipt_detail_id');
        $dt = $dt->get();

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['warehouse_receipt_detail_id'] = $args['warehouse_receipt_detail_id'] ?? null;
        $params['rack_id'] = $args['rack_id'] ?? null;
        $params['qty'] = $args['qty'] ?? 0;
        $params['updated_at'] = Carbon::now();
        if(!$params['warehouse_receipt_detail_id']) {
            throw new Exception('Warehouse receipt item is required');
        }
        if(!$params['rack_id']) {
            throw new Exception('Rack item is required');
        }
        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('packaging_old_items')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Container detail not found');
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
        $dt = $dt->where('packaging_old_items.id', $id);
        $dt = $dt->select('packaging_old_items.id', 'packaging_old_items.packaging_id', 'warehouse_receipt_details.item_id', 'packaging_old_items.qty', 'packaging_old_items.warehouse_receipt_detail_id', 'packaging_old_items.rack_id');
        $dt = $dt->first();

        return $dt;
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function storeMultiple($details, $packaging_id) {
        if(is_array($details)) {
            self::clearStock($packaging_id);
            self::clear($packaging_id);
            foreach($details as $detail) {
                $detail = (array) $detail;
                self::store($detail, $packaging_id);
            }
        }
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params, $packaging_id) {
        $insert = self::fetch($params);
        $insert['created_at'] = Carbon::now();
        $insert['packaging_id'] = $packaging_id;
        $id = DB::table('packaging_old_items')->insertGetId($insert);
        
        self::doRequestOutbound($id);
    }

    public static function doRequestOutbound($id) {
        $dt = self::show($id);
        $packaging = Packaging::show($dt->packaging_id);
        $item = Item::show($dt->item_id);
        $stock['description'] = 'Telah direncanakan packaging ' . $item->name . ' pada transaksi ' . $packaging->code;
        $stock['date_transaction'] = $packaging->date;
        $stock['qty_keluar'] = $dt->qty;
        $stock['warehouse_receipt_detail_id'] = $dt->warehouse_receipt_detail_id;
        $stock['rack_id'] = $dt->rack_id;
        $stock['type_transaction'] = 'packaging';

        
        $request_stock_transaction_id = StockTransaction::doRequestOutbound($stock);
        self::query()->where('packaging_old_items.id', $id)->update([
            'requested_stock_transaction_id' => $request_stock_transaction_id
        ]);
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
        DB::table('packaging_old_items')
        ->whereId($id)
        ->update($update);
    }
    
    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table('packaging_old_items')
        ->whereId($id)
        ->delete();
    }

    /*
      Date : 14-03-2021
      Description : Menghapus stok
      Developer : Didin
      Status : Create
    */
    public static function clearStock($packaging_id) {
        $items = self::index($packaging_id)->where('requested_stock_transaction_id', '!=', null)->pluck('requested_stock_transaction_id')->toArray();
        DB::table(self::$table)->wherePackagingId($packaging_id)->update([
            'requested_stock_transaction_id' => null
        ]);
        StockTransaction::destroyMultiple($items);
    }
}
