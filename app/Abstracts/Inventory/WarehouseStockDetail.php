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
use App\Abstracts\Inventory\ItemMigration;
use App\Abstracts\WarehouseReceipt;

class WarehouseStockDetail
{
    protected static $table = 'warehouse_stock_details';

    /*
      Date : 19-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $request = self::fetchFilter($params);
        $dt = DB::table('warehouse_stock_details');
        $dt = $dt->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'warehouse_stock_details.warehouse_receipt_detail_id');
        $dt = $dt->leftJoin('items', 'items.id', 'warehouse_stock_details.item_id');
        $dt = $dt->leftJoin('categories', 'categories.id', 'items.category_id');
        $dt = $dt->leftJoin('categories AS parents', 'parents.id', 'categories.parent_id');
        $dt = $dt->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id');
        $dt = $dt->join('racks', 'racks.id', 'warehouse_stock_details.rack_id');
        $dt = $dt->join('warehouses', 'warehouses.id', 'racks.warehouse_id');
        $dt = $dt->leftJoin('contacts', 'warehouse_receipts.customer_id', 'contacts.id');

        if($request['warehouse_receipt_detail_id']) {
            $dt = $dt->where(self::$table . '.warehouse_receipt_detail_id', $request['warehouse_receipt_detail_id']);
        }

        if($request['rack_id']) {
            $dt = $dt->where(self::$table . '.rack_id', $request['rack_id']);
        }

        return $dt;
    }

    /*
      Date : 23-03-2021
      Description : Mengambil parameter untuk input data
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($args = []) {
        $params['rack_id'] = $args['rack_id'] ?? null;
        $params['warehouse_receipt_detail_id'] = $args['warehouse_receipt_detail_id'] ?? null;

        return $params;
    }

    /*
      Date : 19-03-2021
      Description : Mengquery data berdasarkan bin location
      Developer : Didin
      Status : Create
    */
    public static function stockByRackQuery() {
        $dt = self::query();
        $dt = $dt->select('warehouse_stock_details.rack_id', 'warehouse_stock_details.warehouse_receipt_detail_id', DB::raw('warehouse_stock_details.qty AS qty_sisa'));

        return $dt;
    }

    /*
      Date : 19-03-2021
      Description : Mengambil filter stocklist
      Developer : Didin
      Status : Create
    */
    public static function fetchStocklistFilter($args = []) {
        $params = [];
        $params['keyword'] = $args['keyword'] ?? null;
        $params['is_pallet'] = $args['is_pallet'] ?? null;
        $params['item_migration_id'] = $args['item_migration_id'] ?? null;
        $params['customer_id'] = $args['customer_id'] ?? null;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['is_not_cancel'] = $args['is_not_cancel'] ?? null;
        $params['is_customer'] = $args['is_customer'] ?? null;
        $params['is_merchandise'] = $args['is_merchandise'] ?? null;
        $params['start_qty'] = $args['start_qty'] ?? null;
        $params['end_qty'] = $args['end_qty'] ?? null;
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
      Date : 19-03-2021
      Description : Mengquery data untuk menu stocklist
      Developer : Didin
      Status : Create
    */
    public static function stocklist($params = []) {
        $params = self::fetchStocklistFilter($params);
        $item = self::query();
        $item = $item->leftJoin('companies', 'companies.id', 'warehouses.company_id');
        $item = $item->select(
            'warehouse_receipts.code AS no_surat_jalan', 
            'contacts.name AS customer_name', 
            'warehouse_receipts.sender', 
            'warehouse_receipts.warehouse_id',
            'warehouse_receipts.receiver', 
            DB::raw('COALESCE(warehouse_receipt_details.item_name, items.name) AS name'), 
            'warehouses.name AS warehouse_name', 
            'warehouse_receipt_details.id AS warehouse_receipt_detail_id', 
            'warehouse_receipts.receive_date', 
            'warehouse_stock_details.qty', 
            'warehouse_receipts.status', 'warehouse_stock_details.onhand_qty', 
            'warehouse_stock_details.available_qty', 'racks.code AS rack_name', 'companies.name AS company_name', 'warehouse_stock_details.item_id');


        $item = self::filterStocklist($params, $item);

        $dt = DB::query()->fromSub($item, 'warehouse_stock_details');

        return $dt;
    }

    public static function filterStocklist($params, $item) {

        if($params['is_customer']) {
            $item = $item->whereNotNull('warehouse_receipts.customer_id');
        }

        if($params['is_merchandise']) {
            $item = $item->where('items.is_merchandise', $params['is_merchandise']);
        }

        if($params['is_pallet']) {
            $item = $item->where(function($query){
                $query->where('parents.is_pallet', 1);
                $query->orWhere('categories.is_pallet', 1);
            });
        }

        if($params['customer_id']) {
            $item = $item->where('warehouse_receipts.customer_id', $params['customer_id']);
        }


        if($params['warehouse_id']) {
            $item = $item->where('warehouse_receipts.warehouse_id', $params['warehouse_id']);
        }

        if($params['start_date']) {
            $start_date = $params['start_date'];
            $item = $item->where('warehouse_receipts.receive_date', '>=', $start_date);
        }

        if($params['end_date']) {
            $end_date = $params['end_date'];
            $item = $item->where('warehouse_receipts.receive_date', '<=', $end_date);
        }

        if($params['start_qty']) {
            $item = $item->where('warehouse_stock_details.qty', '>=', $params['start_qty']);
        }

        if($params['end_qty']) {
            $item = $item->where('warehouse_stock_details.qty', '<=', $params['end_qty']);
        }

        if($params['keyword']) {
            $item = $item->where(function($query) use ($params){
                $query->where('items.name', 'LIKE', '%' . $params['keyword'] . '%');
            });
        }

        if($params['item_migration_id']) {
            $warehouses = ItemMigration::getWarehouses($params['item_migration_id']);
            $items = ItemMigration::getItems($params['item_migration_id']);

            $item = $item->whereIn("warehouses.id", $warehouses);
            $item = $item->whereIn("items.id", $items);
        }

        if($params['is_not_cancel']) {
            $item = $item->where('warehouse_receipts.status', '<', 2);
        }

        return $item;
    }
}
