<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Rack;
use App\Abstracts\Setting\TypeTransaction;
use App\Abstracts\Inventory\WarehouseReceiptDetail;
use App\Abstracts\Inventory\WarehouseStockDetail;
use App\Abstracts\WarehouseReceipt;

class StockTransaction 
{
    protected static $table = 'stock_transactions';

    /*
      Date : 29-08-2020
      Description : Menambah barang pada stock transaction
      Developer : Didin
      Status : Create
    */
    public static function cekStok($params = []) {
        $stockTransaction = DB::table('stock_transactions')
        ->groupBy('stock_transactions.warehouse_receipt_detail_id');

        foreach ($params as $column => $val) {
            $stockTransaction = $stockTransaction->where($column, $val);
        }

        $stock = $stockTransaction->sum(DB::raw('stock_transactions.qty_masuk - stock_transactions.qty_keluar'));
        return $stock;
    }
    
    /*
      Date : 19-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $dt = DB::table(self::$table);
        $dt = $dt->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'stock_transactions.warehouse_receipt_detail_id');
        $dt = $dt->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id');
        $dt = $dt->leftJoin('contacts', 'contacts.id', 'warehouse_receipts.customer_id');
        $dt = $dt->leftJoin('racks', 'racks.id', 'stock_transactions.rack_id');
        $dt = $dt->leftJoin('warehouses', 'warehouses.id', 'racks.warehouse_id');

        $stock = WarehouseStockDetail::stockByRackQuery();
        $dt = $dt->leftJoinSub($stock, 'stocks', function($join){
            $join->on('stocks.rack_id', 'stock_transactions.rack_id');
            $join->on('stocks.warehouse_receipt_detail_id', 'stock_transactions.warehouse_receipt_detail_id');
        });

        $params = self::fetchFilter($params);
        if($params['warehouse_id']) {
            $dt = $dt->where('stock_transactions.warehouse_id', $params['warehouse_id']);
        }

        if($params['start_date']) {
            $dt = $dt->where('stock_transactions.date_transaction', '>=', $params['start_date']);
        }

        if($params['end_date']) {
            $dt = $dt->where('stock_transactions.date_transaction', '<=', $params['end_date']);
        }

        if($params['customer_id']) {
            $dt = $dt->where('warehouse_receipts.customer_id', $params['customer_id']);
        }

        if($params['is_zero']) {
            $dt = $dt->where('stocks.qty_sisa', '>', 0);
        }

        if($params['is_not_cancel']) {
            $dt = $dt->where('warehouse_receipts.status', 1);
        }

        return $dt;
    }

    public static function indexByWarehouseReceiptDetail($warehouse_receipt_detail_id) {
        $dt = self::query();
        $dt = $dt->where('stock_transactions.warehouse_receipt_detail_id', $warehouse_receipt_detail_id);
        $dt = $dt->orderBy(self::$table . '.created_at', 'DESC');
        $dt = $dt->get();

        return $dt;
    }

    /*
      Date : 19-03-2021
      Description : Mengambil data terakhir yang masuk
      Developer : Didin
      Status : Create
    */
    public static function getLastData() {
        $dt = self::query();
        $dt = $dt->orderBy('stock_transactions.id', 'DESC');
        $dt = $dt->select('stock_transactions.*');
        $dt = $dt->first();

        return $dt;
    }
    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params = [];
        $params['date_transaction'] = $args['date_transaction'] ?? null;
        if(!$params['date_transaction']) {
            throw new Exception('Date transaction is required in stock transaction');
        }
        $params['description'] = $args['description'] ?? null;
        $params['warehouse_receipt_detail_id'] = $args['warehouse_receipt_detail_id'] ?? null;
        if($params['warehouse_receipt_detail_id']) {
            $wrd = WarehouseReceiptDetail::show($params['warehouse_receipt_detail_id']);
            $params['rack_id'] = $wrd->rack_id;
            $params['item_id'] = $wrd->item_id;
            $wr = WarehouseReceipt::show($wrd->header_id);
            $params['customer_id'] = $wr->customer_id;
        } else {
            $params['item_id'] = $args['item_id'] ?? null;
        }

        if(($args['rack_id'] ?? null)) {
            $params['rack_id'] = $args['rack_id'] ?? null;
        }
        if(!$params['rack_id']) {
            throw new Exception('Rack is required in stock transaction');
        } else {
            $rack = Rack::show($params['rack_id']);
            $params['warehouse_id'] = $rack->warehouse_id;
        }

        $type_transaction = $args['type_transaction'] ?? null;
        if(!$type_transaction) {
            throw new Exception('Type transaction slug is required in stock transaction');
        } else {
            $tt = TypeTransaction::showBySlug($type_transaction);
            $params['type_transaction_id'] = $tt->id;
        }
        return $params;
    }

    public static function fetchFilter($args = []) {
        $params = [];

        $params['is_zero'] = $args['is_zero'] ?? null;
        $params['is_not_cancel'] = $args['is_not_cancel'] ?? null;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['customer_id'] = $args['customer_id'] ?? null;
        $params['start_date'] = $args['start_date'] ?? null;
        $params['end_date'] = $args['end_date'] ?? null;

        if($params['start_date']) {
            $params['start_date'] = Carbon::parse($params['start_date'])->format('Y-m-d');
        }

        if($params['end_date']) {
            $params['end_date'] = Carbon::parse($params['end_date'])->format('Y-m-d');
        }

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Memasukkan barang ke stok
      Developer : Didin
      Status : Create
    */
    public static function doInbound($params) {
        $qty_masuk = $params['qty_masuk'] ?? 0;
        $warehouse_receipt_id = $params['warehouse_receipt_id'] ?? null;
        if(!$warehouse_receipt_id) {
            throw new Exception('Warehouse receipt is required in inbound stock transaction');
        }
        if(!$params['item_id']) {
            throw new Exception('Item is required inbound in stock transaction');
        }
        $insert['qty'] = $params['qty_masuk'] ?? 0;
        $insert['item_id'] = $params['item_id'] ?? null;
        $insert['rack_id'] = $params['rack_id'] ?? null;
        $insert['header_id'] = $warehouse_receipt_id;
        $insert['imposition'] = 3;
        $insert['storage_type'] = 'RACK';
        $insert['is_exists'] = 1;
        WarehouseReceiptDetail::store($insert, $warehouse_receipt_id);
        $latest = DB::table(self::$table)
        ->orderBy('id', 'DESC')
        ->first();

        if($latest) {
            return $latest->id;
        }
    }

    /*
      Date : 05-03-2021
      Description : Mengeluarkan barang dari stok
      Developer : Didin
      Status : Create
    */
    public static function doOutbound($params) {
        $insert = self::fetchOutbound($params);
        self::validatePhysically($params['warehouse_receipt_detail_id'], $params['rack_id'], $params['qty_keluar']);
        $insert['is_approve'] = 1;
        $id = DB::table('stock_transactions')
        ->insertGetId($insert);

        return $id;
    }

    /*
      Date : 05-03-2021
      Description : Membuat rencana pengeluaran barang
      Developer : Didin
      Status : Create
    */
    public static function doRequestOutbound($params, $check_item = false, $existing = []) {
        $offset = 0;
        $insert = self::fetchOutbound($params, $existing);
        $qty = $insert["qty_keluar"];
        self::validateAvailibity($params['warehouse_receipt_detail_id'], $params['rack_id'], $params['qty_keluar'], $check_item);
        $insert['is_approve'] = 0;
        if($check_item) {
            $avail = self::getAvailibity($insert['warehouse_receipt_detail_id'], $insert['rack_id'], $params['qty_keluar']);
            if($qty > $avail && $avail > 0) {
                $offset = $qty - $avail;
                $params["qty_keluar"] = $offset;
                $insert["qty_keluar"] = $avail;
            }

        } 

        $id = DB::table(self::$table)
        ->insertGetId($insert);

        if($offset > 0) {
            $existing[] = $id;
            self::doRequestOutbound($params, $check_item, $existing);
        }

        return $id;
    }

    /*
      Date : 05-03-2021
      Description : Memeriksa ketersediaan barang
      Developer : Didin
      Status : Create
    */
    public static function getAvailibity($warehouse_receipt_detail_id, $rack_id = null, $qty = 0, $check_item = false) {
        if($check_item) {
            $wrd = WarehouseReceiptDetail::show($warehouse_receipt_detail_id);
            $item_id = $wrd->item_id;
            $stock = WarehouseStockDetail::query();
            $stock = $stock->where('warehouse_stock_details.item_id', $item_id);
            $avail = $stock->sum('available_qty');
        } else {
            $stock = WarehouseStockDetail::query();
            if($rack_id) {
                $stock = $stock->where('warehouse_stock_details.rack_id', $rack_id);
            }
            $stock = $stock->where('warehouse_stock_details.warehouse_receipt_detail_id', $warehouse_receipt_detail_id);
            $avail = $stock->sum('available_qty');
        }


        return $avail;
    }

    /*
      Date : 05-03-2021
      Description : Memeriksa ketersediaan barang
      Developer : Didin
      Status : Create
    */
    public static function checkAvailibity($warehouse_receipt_detail_id, $rack_id, $qty, $check_item = false) {
        $avail = self::getAvailibity($warehouse_receipt_detail_id, $rack_id, $qty, $check_item);

        $r = true;

        if($qty > $avail) {
            $r = false;
        }

        return $r;
    }

    /*
      Date : 05-03-2021
      Description : Validasi ketersediaan barang
      Developer : Didin
      Status : Create
    */
    public static function validateAvailibity($warehouse_receipt_detail_id, $rack_id, $qty, $check_item = false) {
        $enough = self::checkAvailibity($warehouse_receipt_detail_id, $rack_id, $qty, $check_item);

        if(!$enough) {
            $wod = WarehouseReceiptDetail::show($warehouse_receipt_detail_id);
            $item_name = $wod->item_name;
            $msg = 'Available ' . $item_name . ' stock is insufficient';
            throw new Exception($msg);
        }
    }

    /*
      Date : 05-03-2021
      Description : Validasi stok barang secara fisik
      Developer : Didin
      Status : Create
    */
    public static function validatePhysically($warehouse_receipt_detail_id, $rack_id, $qty) {
        $stock = WarehouseStockDetail::query();
        $stock = $stock->where('warehouse_stock_details.rack_id', $rack_id);
        $stock = $stock->where('warehouse_stock_details.warehouse_receipt_detail_id', $warehouse_receipt_detail_id);
        $avail = $stock->sum('warehouse_stock_details.qty');
        if($qty > $avail) {
            $wod = WarehouseReceiptDetail::show($warehouse_receipt_detail_id);
            $item_name = $wod->item_name;
            $msg = $item_name . ' stock is insufficient';
            throw new Exception($msg);
        }
    }

    /*
      Date : 19-03-2021
      Description : Mengambil parameter untuk pengeluaran barang
      Developer : Didin
      Status : Create
    */
    public static function fetchOutbound($params = [], $existing = []) {
        if(!$params['warehouse_receipt_detail_id']) {
            throw new Exception('Warehouse receipt is required in stock transaction');
        }
        $qty_keluar = $params['qty_keluar'] ?? 0;
        $insert = self::fetch($params);

        if(is_array($existing)) {
            if(count($existing) > 0) {
                $stock = DB::table('warehouse_stock_details');
                foreach($existing as $id) {
                    $dt = self::show($id);
                    $stock = $stock->where('item_id', $dt->item_id);
                    $stock = $stock->whereRaw('available_qty > ' . $qty_keluar);
                    $stock = $stock->where(function($query) use ($dt) {
                        $query->where('rack_id', '!=', $dt->rack_id);
                        $query->orWhere('warehouse_receipt_detail_id', '!=', $dt->warehouse_receipt_detail_id);
                    });
                }

                $stock = $stock->first();
                if($stock) {
                    $insert['rack_id'] = $stock->rack_id;
                    $insert['warehouse_receipt_detail_id'] = $stock->warehouse_receipt_detail_id;
                }
            }
        }

        $insert['qty_masuk'] = 0;
        $insert['qty_keluar'] = $qty_keluar;

        return $insert;
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        return $dt;
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
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        DB::beginTransaction();
        self::validate($id);
        DB::table('stock_transactions_report')
        ->whereHeaderId($id)
        ->delete();

        DB::table(self::$table)
        ->whereId($id)
        ->delete();
        DB::commit();
    }

    /*
      Date : 14-03-2021
      Description : Hapus multiple data
      Developer : Didin
      Status : Create
    */
    public static function destroyMultiple($items = []) {
        if(is_array($items)) {
            foreach($items as $id) {
                self::destroy($id);
            }
        }
    }
}
