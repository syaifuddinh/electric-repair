<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Inventory\StockTransaction;
use App\Abstracts\Inventory\PackagingNewItem;
use App\Abstracts\Inventory\PackagingOldItem;
use App\Abstracts\Inventory\WarehouseReceiptDetail;

class Packaging extends StockTransaction
{
    protected static $table = 'packagings';

    public static function fetchFilter($args = []) {
        $params = [];
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
      Date : 17-03-2021
      Description : Meng-query data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $params = self::fetchFilter($params);
        $dt = DB::table('packagings');
        $dt = $dt->join('users', 'users.id', 'packagings.created_by');
        $dt = $dt->leftJoin('companies', 'companies.id', 'packagings.company_id');
        $dt = $dt->leftJoin('warehouses', 'warehouses.id', 'packagings.warehouse_id');

        if($params['start_date']) {
            $dt = $dt->where('date', '>=', $params['start_date']);
        }

        if($params['end_date']) {
            $dt = $dt->where('date', '>=', $params['end_date']);
        }

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
        $params['updated_at'] = Carbon::now();
        $params['date'] = $args['date'] ?? null;
        $params['company_id'] = $args['company_id'] ?? null;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['description'] = $args['description'] ?? null;
        if($params['date']) {
            Checker::checkDate($params['date']);
            $params['date'] = Carbon::parse($params['date'])->format('Y-m-d');
        }

        if(!$params['company_id']) {
            throw new Exception('Branch / company is required');
        } else {
            Checker::checkCompany($params['company_id']);
        }

        if(!$params['warehouse_id']) {
            throw new Exception('Warehouse is required');
        } else {
            Checker::checkWarehouse($params['warehouse_id']);
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
        $dt = DB::table('packagings')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Packaging not found');
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
        $dt = $dt->where('packagings.id', $id);
        $dt = $dt->select('packagings.id', 'packagings.code', 'packagings.created_at', 'packagings.date', 'packagings.company_id', 'packagings.warehouse_id', 'packagings.description', 'users.name AS creator_name', 'packagings.is_approve', 'companies.name AS company_name', 'warehouses.name AS warehouse_name');
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
        $created_by = $params['created_by'] ?? null;
        $insert = self::fetch($params);
        if(!$created_by) {
            $created_by = auth()->id();
        }
        $created_at = Carbon::now();
        $insert['created_by'] = $created_by;
        $insert['created_at'] = $created_at;
        $code = new TransactionCode($insert['company_id'], 'packaging');
        $code->setCode();
        $trx_code = $code->getCode();
        $insert['code'] = $trx_code;
        $id = DB::table('packagings')->insertGetId($insert);
        return $id;
    }
    
    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params, $id) {
        self::validateIsApproved($id);
        $update = self::fetch($params);
        DB::table('packagings')
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
        PackagingOldItem::clearStock($id);
        PackagingOldItem::clear($id);
        PackagingNewItem::clear($id);
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }

    public static function doOutbound($id) {
        $items = PackagingOldItem::index($id);
        if(count($items) == 0) {
            throw new Exception('Old item is required');
        }

        $dt = self::show($id);
        foreach($items as $item) {
            $params = [];
            $params['description'] = 'Telah dilakukan packaging / dispatch pada barang ' . $item->item_name . ' pada transaksi #' . $dt->code;
            $params['warehouse_receipt_detail_id'] = $item->warehouse_receipt_detail_id;
            $params['rack_id'] = $item->rack_id;
            $params['date_transaction'] = $dt->date;
            $params['type_transaction'] = 'packaging';
            $params['qty_keluar'] = $item->qty;
            parent::doOutbound($params);
        }
    }

    public static function doInbound($id) {
        $items = PackagingNewItem::index($id);
        $old_items = PackagingOldItem::index($id);
        $warehouse_receipt_detail_id = $old_items[0]->warehouse_receipt_detail_id;
        $wrd = WarehouseReceiptDetail::show($warehouse_receipt_detail_id);
        $warehouse_receipt_id = $wrd->header_id;
        $rack_id = $wrd->rack_id;
        $dt = self::show($id);

        foreach($items as $item) {
            $params = [];
            $params['warehouse_receipt_id'] = $warehouse_receipt_id;
            $params['item_id'] = $item->item_id;
            $params['rack_id'] = $rack_id;
            $params['date_transaction'] = $dt->date;
            $params['qty_masuk'] = $item->qty;
            parent::doInbound($params);
        }
    }

    public static function approve($id) {
        self::validateIsApproved($id);
        self::doOutbound($id);
        self::doInbound($id);
        DB::table('packagings')
        ->whereId($id)
        ->update([
            'is_approve' => 1
        ]);
    }

    public static function validateIsApproved($id) {
        $dt = self::show($id);
        if($dt->is_approve == 1) {
            throw new Exception('This data was approved');
        }
    }
}
