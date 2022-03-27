<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\StockTransaction;
use App\Abstracts\Inventory\ItemDeletion;
use App\Abstracts\Inventory\Item;
use App\Abstracts\Inventory\WarehouseReceiptDetail;
use App\Abstracts\Inventory\WarehouseStockDetail;
use App\Abstracts\WarehouseReceipt;

class ItemDeletionDetail
{
    protected static $table = 'item_deletion_details';
    protected static $activity = 'penghapusan barang';

    /*
      Date : 05-03-2021
      Description : Menampilkan item pada putaway
      Developer : Didin
      Status : Create
    */
    public static function index($item_deletion_id = null) {
        $wsd = WarehouseStockDetail::query()->select('warehouse_stock_details.rack_id', 'warehouse_stock_details.warehouse_receipt_detail_id', DB::raw("SUM(warehouse_stock_details.qty) AS qty"));

        $wsd = $wsd->groupBy('warehouse_stock_details.warehouse_receipt_detail_id', 'warehouse_stock_details.rack_id');

        $stocks = DB::query()->fromSub($wsd, 'stocks');

        $dt = DB::table(self::$table);
        if($item_deletion_id) {
            $dt = $dt->where(self::$table . '.header_id', $item_deletion_id);
        }

        $dt = $dt->join('items', 'items.id', self::$table . '.item_id');
        $dt = $dt->join('racks', 'racks.id', self::$table . '.rack_id');
        $dt = $dt->join('warehouse_receipt_details', 'warehouse_receipt_details.id', self::$table . '.warehouse_receipt_detail_id');
        $dt = $dt->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id');
        $dt = $dt->leftJoinSub($stocks, 'stocks', function($join){
            $join->on('stocks.rack_id', self::$table . '.rack_id');
            $join->on('stocks.warehouse_receipt_detail_id', self::$table . '.warehouse_receipt_detail_id');
        });
        $dt = $dt->select(self::$table . '.*', 'items.name AS item_name', 'items.code AS item_code', 'racks.code AS rack_code', 'warehouse_receipts.code AS warehouse_receipt_code', DB::raw("COALESCE(stocks.qty, 0) AS stock"));
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
        $dt = DB::table(self::$table);

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
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(self::$table . '.*');
        $dt = $dt->first();
        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Ubah jumlah barang
      Developer : Didin
      Status : Create
    */
    public static function updateQty($qty, $id) {
        $dt = self::show($id);
        ItemDeletion::validateIsTakeout($dt->header_id);
        ItemDeletion::validateIsTakein($dt->header_id);
        DB::table(self::$table)->whereId($id)
        ->update([
            'qty' => $qty
        ]);

        self::resetRequestOutbound($dt->header_id);
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi picking detail
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Item deletion detail not found');
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
            if($wrd->item_id) {
                $params['item_id'] = $wrd->item_id;
            } else {
                $params['item_id'] = $args['item_id'] ?? null;
            }
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
    public static function storeMultiple($details, $item_deletion_id) {
        if(is_array($details)) {
            self::clear($item_deletion_id);
            foreach($details as $detail) {
                $detail = (array) $detail;
                self::store($detail, $item_deletion_id);
            }
        }
    }

    /*
      Date : 05-03-2021
      Description : Menghapus semua data
      Developer : Didin
      Status : Create
    */
    public static function clear($item_deletion_id) {
        ItemDeletion::validate($item_deletion_id);
        $dt = self::query();
        $dt = $dt->where(self::$table . '.header_id', $item_deletion_id);
        $dt->delete();
    }

    /*
      Date : 23-03-2021
      Description : Memasukkan perencanaan ke dalam stock transaction
      Developer : Didin
      Status : Create
    */
    public static function doRequestOutbound($id) {
        $dt = self::show($id);
        $header = ItemDeletion::show($dt->header_id);
        $item = Item::show($dt->item_id);
        $stock['description'] = 'Telah direncanakan ' . self::$activity . ' ' . $item->name . ' pada transaksi ' . $header->code;
        $stock['date_transaction'] = $header->date_transaction;
        $stock['qty_keluar'] = $dt->qty;
        $stock['warehouse_receipt_detail_id'] = $dt->warehouse_receipt_detail_id;
        $stock['type_transaction'] = ItemDeletion::$type_transaction;

        $stock['rack_id'] = $dt->rack_id;
        $request_stock_transaction_id = StockTransaction::doRequestOutbound($stock);
        self::query()->where(self::$table . '.id', $id)->update([
            'requested_stock_transaction_id' => $request_stock_transaction_id
        ]);
    }

    public static function doRequestOutboundMultiple($item_deletion_id) {
        $items = self::index($item_deletion_id);
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
    public static function clearStock($item_deletion_id) {
        $items = self::index($item_deletion_id)->where('requested_stock_transaction_id', '!=', null)->pluck('requested_stock_transaction_id')->toArray();
        DB::table(self::$table)->whereHeaderId($item_deletion_id)->update([
            'requested_stock_transaction_id' => null
        ]);
        StockTransaction::destroyMultiple($items);
    }

    /*
      Date : 14-03-2021
      Description : Reset permintaan pengeluaran barang
      Developer : Didin
      Status : Create
    */
    public static function resetRequestOutbound($item_deletion_id) {
        self::clearStock($item_deletion_id);
        self::doRequestOutboundMultiple($item_deletion_id);
    }

    /*
      Date : 14-03-2021
      Description : Membuat pengeluaran barang untuk 1 barang
      Developer : Didin
      Status : Create
    */
    public static function doOutbound($id) {
        $dt = self::show($id);
        $header = ItemDeletion::show($dt->header_id);
        $params = [];
        $params['qty_keluar'] = $dt->qty;
        $params['date_transaction'] = Carbon::now();
        $params['description'] = 'Pengeluaran Barang pada ' . self::$activity . ' #' . $header->code;
        $params['rack_id'] = $dt->rack_id;
        $params['warehouse_receipt_detail_id'] = $dt->warehouse_receipt_detail_id;
        $params['item_id'] = $dt->item_id;
        $params['type_transaction'] = ItemDeletion::$type_transaction;
        $stock_transaction_id = StockTransaction::doOutbound($params);
        self::updateOutbound($stock_transaction_id, $id);

    }

    /*
      Date : 14-03-2021
      Description : Membuat penerimaan barang untuk 1 barang
      Developer : Didin
      Status : Create
    */
    public static function doInbound($id) {
        $dt = self::show($id);
        $wrd = WarehouseReceiptDetail::show($dt->warehouse_receipt_detail_id);
        $header = ItemDeletion::show($dt->header_id);
        $params = [];
        $params['qty_masuk'] = $dt->qty;
        $params['date_transaction'] = Carbon::now();
        $params['description'] = 'Penerimaan Barang dari putaway - #' . $header->code;
        $params['rack_id'] = $dt->destination_rack_id;
        $params['warehouse_receipt_id'] = $wrd->header_id;
        $params['item_id'] = $dt->item_id;
        $params['type_transaction'] = ItemDeletion::$type_transaction;
        $stock_transaction_id = StockTransaction::doInbound($params);
        self::updateInbound($stock_transaction_id, $id);

    }

     /*
      Date : 14-03-2021
      Description : Membuat pengeluaran barang untuk 1 barang
      Developer : Didin
      Status : Create
    */
    public static function doMultipleOutbound($item_deletion_id) {
        $items = self::index($item_deletion_id);
        $items->each(function($item) {
            self::doOutbound($item->id);
        });
    }

     /*
      Date : 14-03-2021
      Description : Membuat pengeluaran barang untuk banyak barang
      Developer : Didin
      Status : Create
    */
    public static function doMultipleInbound($item_deletion_id) {
        $items = self::index($item_deletion_id);
        $items->each(function($item) {
            self::doInbound($item->id);
        });
    }

    /*
      Date : 14-03-2021
      Description : Update riwayat pengeluaran stok pada item putaway
      Developer : Didin
      Status : Create
    */
    public static function updateOutbound($stock_transaction_id, $id) {
        StockTransaction::validate($stock_transaction_id);
        self::validate($id);

        DB::table(self::$table)->whereId($id)
        ->update([
            'outbound_stock_transaction_id' => $stock_transaction_id
        ]);
    }

    /*
      Date : 14-03-2021
      Description : Update riwayat pengeluaran stok pada item putaway
      Developer : Didin
      Status : Create
    */
    public static function updateInbound($stock_transaction_id, $id) {
        StockTransaction::validate($stock_transaction_id);
        self::validate($id);

        DB::table(self::$table)->whereId($id)
        ->update([
            'inbound_stock_transaction_id' => $stock_transaction_id
        ]);
    }
}
