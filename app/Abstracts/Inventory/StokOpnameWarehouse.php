<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\StockTransaction;
use App\Abstracts\Inventory\Picking;
use App\Abstracts\Inventory\PickingDetail;

class StokOpnameWarehouse
{
    protected static $table = 'stok_opname_warehouses';

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
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.id', $id);
        
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
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Picking not found');
        }
    }

    /*
      Date : 23-03-2021
      Description : Mengambil parameter untuk input data
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params['item_id'] = $args['item_id'] ?? null;
        $params['rack_id'] = $args['rack_id'] ?? null;
        $params['qty'] = $args['qty'] ?? 0;
        $params['warehouse_receipt_detail_id'] = $args['warehouse_receipt_detail_id'] ?? null;

        if(!$params['item_id']) {
            throw new Exception('Item is required');
        }

        if(!$params['rack_id']) {
            throw new Exception('Rack / bin location is required');
        }

        if(!$params['warehouse_receipt_detail_id']) {
            throw new Exception('Warehouse receipt detail location is required');
        }
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
        $id = DB::table('pickings')
        ->insertGetId($params);

        self::doRequestOutbound($id);
    }

    /*
      Date : 23-03-2021
      Description : Memperoleh status yang tipe nya disetujui
      Developer : Didin
      Status : Create
    */
    public static function getApproveStatus() {
        return 2;
    }

    /*
      Date : 23-03-2021
      Description : Memvalidasi apakah data sudah disetujui atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsApproved($id) {
        $dt = self::show($id);
        $approveStatus = self::getApproveStatus();
        if($dt->status == $approveStatus) {
            throw new Exception('Data was approved');
        }
    }

    /*
      Date : 23-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        PickingDetail::clear($id);
        DB::table(self::$table)->whereId($id)->delete();
    }
}
