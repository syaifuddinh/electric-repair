<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Setting;
use App\Abstracts\Setting\Math;
use App\Abstracts\Inventory\ItemType;
use App\Abstracts\Inventory\Picking;
use App\Abstracts\Rack;
use App\Model\QuotationItem;
use Illuminate\Support\Str;

class Item
{
    protected static $table = 'items';

    public static function query($request = []) {
        $request = self::fetchFilter($request);
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('categories','categories.id','items.category_id');
        $dt = $dt->leftJoin('categories as parents','parents.id','categories.parent_id');

        if($request['is_pallet'] == 1) {
            $dt = $dt->where(function($query){
                $query->where('parents.is_pallet', 1);
                $query->orWhere('categories.is_pallet', 1);
            });
        }

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['is_pallet'] = $args['is_pallet'] ?? null;

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan kumpulan data
      Developer : Didin
      Status : Create
    */
    public static function indexByArray($params = []) {
        $dt = DB::table(self::$table);
        $dt = $dt->whereIn(self::$table . '.id', $params);
        $dt = $dt->get();
    }

    /*
      Date : 05-03-2021
      Description : Update harga beli pada master barang
      Developer : Didin
      Status : Create
    */
    public static function setPurchasePrice($id, $price) {
        self::validate($id);
        
        DB::table(self::$table)->whereId($id)->update([
            "harga_beli" => $price
        ]);
    }


    /*
      Date : 05-03-2021
      Description : Function ini harus terintegrasi dengan function itemInWarehouseQuery(), function ini berfungsi untuk mengurutkan data berdasarkan bin location, setting urutan bin location bisa dilihat di setting / general setting / general
      Developer : Didin
      Status : Create
    */
    public static function orderByRack($query) {
        $dt = $query;
        $dt = $dt->leftJoin('rack_maps', 'rack_maps.rack_id', 'racks.id');
        $dt = $dt->leftJoin('warehouse_maps', 'warehouse_maps.id', 'rack_maps.warehouse_map_id');

        $horizontalOrder = Setting::fetchValue('picking', 'bin_location_order');
        $verticalOrder = Setting::fetchValue('picking', 'bin_location_level_order');


        if($horizontalOrder == 'FRONT') {
            $dt = $dt->orderBy('warehouse_maps.row', 'ASC');
        } else if($horizontalOrder == 'BEHIND') {
            $dt = $dt->orderBy('warehouse_maps.row', 'DESC');
        }

        if($verticalOrder == 'TOP') {
            $dt = $dt->orderBy('warehouse_maps.level', 'DESC');
        } else if($verticalOrder == 'BOTTOM') {
            $dt = $dt->orderBy('warehouse_maps.level', 'ASC');
        }

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Filter query stok barang
      Developer : Didin
      Status : Create
    */
    public static function fetchFilterItemInWarehouse($args = []) {
        $params = [];
        $params['is_pallet']= $args['is_pallet'] ?? null;
        $params['warehouse_id']= $args['warehouse_id'] ?? null;
        $params['rack_id']= $args['rack_id'] ?? null;
        $params['warehouse_receipt_id']= $args['warehouse_receipt_id'] ?? null;
        $params['customer_id']= $args['customer_id'] ?? null;
        $params['is_handling_area']= $args['is_handling_area'] ?? null;
        $params['is_picking_area']= $args['is_picking_area'] ?? null;
        $params['show_picking']= $args['show_picking'] ?? null;
        $params['is_merchandise']= $args['is_merchandise'] ?? null;
        $params['quotation_id']= $args['quotation_id'] ?? null;

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Filter query stok barang
      Developer : Didin
      Status : Create
    */
    public static function filterItemInWarehouse($request, $item) {

        if($request['warehouse_id']) {
            $item->where('racks.warehouse_id', $request['warehouse_id']);
        }

        if(isset($request['rack_id'])) {
            $item = $item->where('racks.id', $request['rack_id']);
        }
        if(isset($request['warehouse_receipt_id'])) {
            $item = $item->where('warehouse_receipts.id', $request['warehouse_receipt_id']);
        }
        else if(isset($request['customer_id'])) {
            $item = $item
            ->where('warehouse_receipts.customer_id', $request['customer_id']);
        }

        if($request['is_pallet'] == 1) {
            $item = $item->where(function($query){
                $query->where('parents.is_pallet', 1);
                $query->orWhere('categories.is_pallet', 1);
            });
        }

        if(isset($request['is_handling_area'])) {
            $racks = DB::table('racks')->leftJoin('storage_types', 'storage_type_id', '=', 'storage_types.id')->where('warehouse_id', $request['warehouse_id'])->where('is_handling_area', 1)->select('racks.id')->first();
            if($racks != null) {
                $item = $item->where('racks.id', $racks->id);
            }

        }
        else if(isset($request['is_picking_area'])) {
            $racks = DB::table('racks')->leftJoin('storage_types', 'storage_type_id', '=', 'storage_types.id')->where('warehouse_id', $request['warehouse_id'])->where('is_picking_area', 1)->select('racks.id')->first();
            $item = $item->where('racks.id', $racks->id);

        }

        return $item;
    }

    /*
      Date : 05-03-2021
      Description : Mengquery stok barang
      Developer : Didin
      Status : Create
    */
    public static function itemInWarehouseQuery($request = []) {
        $request = self::fetchFilterItemInWarehouse($request);

        $item = DB::table('items')
        ->leftJoin('pieces', 'pieces.id', 'items.piece_id')
        ->leftJoin('categories','categories.id','items.category_id')
        ->leftJoin('categories as parents','parents.id','categories.parent_id')
        ->join('warehouse_receipt_details', 'warehouse_receipt_details.item_id', 'items.id')
        ->leftJoin('warehouse_stock_details', 'warehouse_stock_details.warehouse_receipt_detail_id', 'warehouse_receipt_details.id')
        ->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
        ->leftJoin('racks', 'warehouse_stock_details.rack_id', 'racks.id')
        ->join('warehouses', 'warehouses.id', 'racks.warehouse_id');


        $item = self::filterItemInWarehouse($request, $item);

        $hasQuotation= isset($request["quotation_id"]) && $request['quotation_id']!=null;

        $qSelect = 'items.harga_jual AS harga_jual,';
        if($hasQuotation){
            $qSelect = 'IFNULL(quotation_items.price, items.harga_jual) AS harga_jual,';
        }
        $item = $item
        ->selectRaw('items.id,
        items.code,
        items.name,
        items.piece_id,'.
        $qSelect
        .'items.category_id,
        pieces.name AS piece_name,
        warehouses.name AS warehouse_name,
        warehouse_receipts.code AS warehouse_receipt_code,
        warehouse_receipts.id AS warehouse_receipt_id,
        warehouse_receipt_details.long,
        warehouse_receipt_details.wide,
        warehouse_receipt_details.high AS height,
        warehouse_receipt_details.weight,
        IFNULL(warehouse_stock_details.available_qty, 0) AS qty,
        racks.id AS rack_id,racks.code AS rack_code,
        warehouse_receipt_details.id AS warehouse_receipt_detail_id,
        warehouse_receipt_details.imposition,
        IF(warehouse_receipt_details.imposition = 1, "Kubikasi", IF(warehouse_receipt_details.imposition = 2, "Tonase", IF(warehouse_receipt_details.imposition = 3, "Item", "Borongan"))) AS imposition_name');

        $item = self::orderByRack($item);

        if($request['is_merchandise'] !== null) {
            $item = $item->where("items.is_merchandise", $request['is_merchandise']);
        }

        if($request['show_picking'] == 1) {
            $item = $item->join('storage_types', 'storage_types.id', 'racks.storage_type_id');
            $item = $item->join('picking_details', 'picking_details.warehouse_receipt_detail_id', 'warehouse_receipt_details.id');
            $item = $item->join('pickings', 'pickings.id', 'picking_details.header_id');
            $item = $item->where('pickings.status', Picking::getApproveStatus());
            $item = $item->where('storage_types.is_picking_area', 1);

            $item = $item->addSelect(['pickings.code AS picking_code', 'picking_details.id AS picking_detail_id', 'picking_details.qty AS picking_detail_qty']);

            $item = $item->where('warehouse_stock_details.available_qty', '>', 0);
        }

        if($request['quotation_id']){
            $item = $item->leftJoin('quotation_items', 'quotation_items.item_id', 'items.id');
            $itemsId = QuotationItem::where('quotation_id', $request['quotation_id'])->get()->pluck('item_id')->toArray();
            $item = $item->whereIn('items.id', $itemsId)->where('quotation_id', $request['quotation_id']);
        }

        return $item;
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['category_id'] = $args['category_id'] ?? null;
        $params['code'] = $args['code'] ?? null;
        $params['name'] = $args['name'] ?? null;
        $params['initial_cost'] = $args['initial_cost'] ?? 0;
        $params['part_number'] = $args['part_number'] ?? null;
        $params['qrcode'] = $args['qrcode'] ?? null;
        $params['barcode'] = $args['barcode'] ?? null;
        $params['sku'] = $args['sku'] ?? null;
        $params['account_id'] = $args['account_id'] ?? null;
        $params['description'] = $args['description'] ?? null;
        $params['is_stock'] = $args['is_stock'] ?? null;
        $params['main_supplier_id'] = $args['main_supplier_id'] ?? null;
        $params['minimal_stock'] = $args['minimal_stock'] ?? 0;
        $params['piece_id'] = $args['piece_id'] ?? null;
        $params['item_type'] = $args['item_type'] ?? null;
        $params['is_expired'] = $args['is_expired'] ?? 0;
        $params['is_service'] = $args['is_service'] ?? 0;

        $params['wide'] = $args['wide'] ?? 0;
        $params['long'] = $args['long'] ?? 0;
        $params['height'] = $args['height'] ?? 0;
        $volume = Math::countVolume($params['long'], $params['wide'], $params['height']);
        $params['volume'] = $volume;

        $params['tonase'] = $args['tonase'] ?? 0;
        $params['vendor_path_no'] = $args['vendor_path_no'] ?? null;
        $params['brand_name'] = $args['brand_name'] ?? null;
        $params['harga_beli'] = $args['harga_beli'] ?? 0;
        $params['harga_jual'] = $args['harga_jual'] ?? 0;
        $params['std_purchase'] = $args['std_purchase'] ?? 0;
        $params['is_accrual'] = $args['is_accrual'] ?? 0;
        $params['is_bbm'] = $args['is_bbm'] ?? 0;
        $params['is_operational'] = $args['is_operational'] ?? 0;
        $params['is_invoice'] = $args['is_invoice'] ?? 0;
        $params['account_purchase_id'] = $args['account_purchase_id'] ?? null;
        $params['account_sale_id'] = $args['account_sale_id'] ?? null;
        $params['account_payable_id'] = $args['account_payable_id'] ?? null;
        $params['account_cash_id'] = $args['account_cash_id'] ?? null;
        $params['is_overtime'] = $args['is_overtime'] ?? 0;
        $params['is_ppn'] = $args['is_ppn'] ?? 0;
        $params['is_dangerous_good'] = $args['is_dangerous_good'] ?? 0;
        $params['is_fast_moving'] = $args['is_fast_moving'] ?? 0;
        $params['is_merchandise'] = $args['is_merchandise'] ?? 0;
        $params['default_rack_id'] = $args['default_rack_id'] ?? null;

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('items')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Item not found');
        }
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validateByName($value) {
        $res = false;
        $value = strtolower($value);
        $dt = DB::table(self::$table)
        ->whereRaw("LOWER(`name`) = '$value'")
        ->count('items.id');

        if($dt > 0) {
            $res = true;
        }

        return $res;
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $data = DB::table(self::$table);
        $data = $data->leftJoin('categories as c','c.id','items.category_id');
        $data = $data->leftJoin('pieces as p','p.id','items.piece_id');
        $data = $data->leftJoin('accounts as ac_item','ac_item.id','items.account_id');
        $data = $data->leftJoin('accounts as ac_payable','ac_payable.id','items.account_payable_id');
        $data = $data->leftJoin('accounts as ac_cash','ac_cash.id','items.account_cash_id');
        $data = $data->leftJoin('accounts as ac_cost','ac_cost.id','items.account_purchase_id');
        $data = $data->leftJoin('accounts as ac_sale','ac_sale.id','items.account_sale_id');
        $data = $data->leftJoin('contacts as v','v.id','items.main_supplier_id');
        $data = $data->leftJoin('racks', 'racks.id', self::$table . '.default_rack_id');
        $data = $data->selectRaw('
        items.*,
        c.name as category,
        p.name as piece,
        racks.code AS rack_code,
        concat(ac_item.code,\'-\',ac_item.name) as account_item,
        concat(ac_payable.code,\'-\',ac_payable.name) as account_payable,
        concat(ac_cash.code,\'-\',ac_cash.name) as account_cash,
        concat(ac_cost.code,\'-\',ac_cost.name) as account_cost,
        concat(ac_sale.code,\'-\',ac_sale.name) as account_sale,
        v.name as vendor
        ');
        $data = $data->where('items.id', $id);
        $data = $data->first();

        $dt = $data;

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail berdasarkan nama
      Developer : Didin
      Status : Create
    */
    public static function showByName($value) {
        $value = strtolower($value);
        $data = DB::table('items');
        $data = $data->leftJoin('categories as c','c.id','items.category_id');
        $data = $data->leftJoin('pieces as p','p.id','items.piece_id');
        $data = $data->leftJoin('accounts as ac_item','ac_item.id','items.account_id');
        $data = $data->leftJoin('accounts as ac_payable','ac_payable.id','items.account_payable_id');
        $data = $data->leftJoin('accounts as ac_cash','ac_cash.id','items.account_cash_id');
        $data = $data->leftJoin('accounts as ac_cost','ac_cost.id','items.account_purchase_id');
        $data = $data->leftJoin('accounts as ac_sale','ac_sale.id','items.account_sale_id');
        $data = $data->leftJoin('contacts as v','v.id','items.main_supplier_id');
        $data = $data->selectRaw('
        items.*,
        c.name as category,
        p.name as piece,
        concat(ac_item.code,\'-\',ac_item.name) as account_item,
        concat(ac_payable.code,\'-\',ac_payable.name) as account_payable,
        concat(ac_cash.code,\'-\',ac_cash.name) as account_cash,
        concat(ac_cost.code,\'-\',ac_cost.name) as account_cost,
        concat(ac_sale.code,\'-\',ac_sale.name) as account_sale,
        v.name as vendor
        ');
        $data = $data->whereRaw("LOWER(items.name) = '$value'");
        $data = $data->first();

        $dt = $data;

        return $dt;
    }


    /*
      Date : 19-04-2021
      Description : Menampilkan detail berdasarkan barcode
      Developer : Didin
      Status : Create
    */
    public static function showByBarcode($value) {

        $dt = [];
        $dt['id'] = Str::random(10);
        $dt['name'] = Str::random(5) . ' ' . Str::random(4);
        $dt['long'] = round(rand() * 500);
        $dt['wide'] = round(rand() * 400);
        $dt['height'] = round(rand() * 600);
        $dt['stock'] = round(rand() * 200);
        $dt['piece_name'] = 'Item';

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
        $insert['is_active'] = 1;
        $update['created_at'] = Carbon::now();
        $id = DB::table(self::$table)->insertGetId($insert);

        return $id;
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan data sebagai pallet
      Developer : Didin
      Status : Create
    */
    public static function storeAsPallet($params) {
        $category_id = ItemCategory::getPallet();
        $params["category_id"] = $category_id;

        return self::storeWithDefault($params);
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data dengan setting default, jadi bisa tanpa kode
      Developer : Didin
      Status : Create
    */
    public static function storeWithDefault($params) {
        $item_name = $params['name'] ?? null;
        $item_code = $params['code'] ?? null;
        if(!$item_code && $item_name) {
            $item_code = strtoupper($item_name);
            $item_code = str_replace(' ', '', $item_name);
            $params['code'] = $item_code;
        }

        if( !($params["is_stock"] ?? null) ) {
            $params['is_stock'] = 1;
        }

        if( !($params["item_type"] ?? null) ) {
            $params['item_type'] = ItemType::getBoth();
        }

        return self::store($params);
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
        $update['updated_at'] = Carbon::now();
        DB::table('items')
        ->whereId($id)
        ->update($update);
    }

    /*
      Date : 14-03-2021
      Description : Memvalidasi barang, apakah sudah tercatat pada stok atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateInStock($id) {
        $exist = DB::table('stock_transactions')
        ->whereItemId($id)
        ->count('id');

        if($exist > 0) {
            throw new Exception('This item has transaction');
        }
    }

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table('items')
        ->whereId($id)
        ->delete();
    }
}
