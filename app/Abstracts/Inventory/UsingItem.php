<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Setting;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Inventory\Warehouse;
use App\Abstracts\Inventory\UsingItemDetail;

class UsingItem 
{
    protected static $table = 'using_items';
    public static $type_transaction = 'pemakaianBarang';
    
    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $dt = DB::table(self::$table);

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan daftar nama item condition
      Developer : Didin
      Status : Create
    */
    public static function index($using_item_id) {
        $params = [];
        if($using_item_id) {
            $params['header_id'] = $using_item_id;
        }
        $dt = self::query($params);
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
        $params['date_request'] = $args['date_request'] ?? null;
        Checker::checkDate($params['date_request']);
        $params['date_request'] = Carbon::parse($params['date_request'])->format('Y-m-d');
        $params['description'] = $args['description'] ?? null;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;

        if(!$params['warehouse_id']) {
            throw new Exception('Warehouse is required');
        } else {
            $wh = Warehouse::show($params['warehouse_id']);
            $params['company_id'] = $wh->company_id;
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
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('UsingItem not found');
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
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('companies', 'companies.id', self::$table . '.company_id');
        $dt = $dt->leftJoin('warehouses', 'warehouses.id', self::$table . '.warehouse_id');
        $dt = $dt->leftJoin('using_item_statuses', 'using_items.status', 'using_item_statuses.id');
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(self::$table . '.*', 'warehouses.name AS warehouse_name', 'companies.name AS company_name', 'using_item_statuses.name AS status_name');
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
        $company_id = $insert['company_id'];
        $code = new TransactionCode($company_id, self::$type_transaction);
        $code->setCode();
        $trx_code = $code->getCode();
        $insert['created_at'] = Carbon::now();
        $insert['code'] = $trx_code;
        $id = DB::table(self::$table)->insertGetId($insert);

        UsingItemDetail::storeMultiple($params['detail'], $id);

        return $id;
    }
    
    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params = [], $id) {
        self::validate($id);
        $detail = $params ['detail'] ?? null;
        $update = self::fetch($params);

        DB::table(self::$table)
        ->whereId($id)
        ->update($update);

        if($detail && is_array($detail)) {
            UsingItemDetail::clearStock($id);
            UsingItemDetail::clear($id);

            UsingItemDetail::storeMultiple($detail, $id);
        }
    }
    
    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validateIsTakeout($id);
        UsingItemDetail::clear($id);
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }

    /*
      Date : 14-03-2021
      Description : Menghapus stok
      Developer : Didin
      Status : Create
    */
    public static function clearStock($using_item_id) {
        $items = self::index($using_item_id)->where('requested_stock_transaction_id', '!=', null)->pluck('requested_stock_transaction_id')->toArray();
        DB::table(self::$table)->whereHeaderId($using_item_id)->update([
            'requested_stock_transaction_id' => null
        ]);
        ST2::destroyMultiple($items);
    }

    /*
      Date : 23-03-2021
      Description : Memperoleh status yang tipe nya draft / request
      Developer : Didin
      Status : Create
    */
    public static function getRequestedStatus() {
        return 1;
    }

    /*
      Date : 23-03-2021
      Description : Memperoleh status yang tipe nya item keluar
      Developer : Didin
      Status : Create
    */
    public static function getOutboundStatus() {
        return 2;
    }

    /*
      Date : 23-03-2021
      Description : Memperoleh status yang tipe nya item masuk
      Developer : Didin
      Status : Create
    */
    public static function getInboundStatus() {
        return 3;
    }

    /*
      Date : 23-03-2021
      Description : Memvalidasi apakah data sudah dikeluarkan atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsTakeout($id) {
        $dt = self::show($id);
        $approveStatus = self::getOutboundStatus();
        if($dt->status == $approveStatus) {
            throw new Exception('Data was take out');
        }
    }

    /*
      Date : 23-03-2021
      Description : Memvalidasi apakah data sudah dimasukkan di rak tujuan atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsTakein($id) {
        $dt = self::show($id);
        $approveStatus = self::getInboundStatus();
        if($dt->status == $approveStatus) {
            throw new Exception('Data was take in at destination bin location');
        }
    }

    /*
      Date : 23-03-2021
      Description : Memvalidasi apakah data sudah dimasukkan di rak tujuan atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsRequested($id) {
        $dt = self::show($id);
        $approveStatus = self::getRequestedStatus();
        if($dt->status == $approveStatus) {
            throw new Exception('Data still requested');
        }
    }

    /*
      Date : 29-08-2021
      Description : Membuat pengeluaran barang
      Developer : Didin
      Status : Create
    */
    public static function itemOut($id) {
        self::validateIsTakeout($id);

        DB::table(self::$table)->whereId($id)
        ->update([
            'status' => self::getOutboundStatus(),
            'date_approve' => Carbon::now()
        ]);

        UsingItemDetail::doMultipleOutbound($id);
    }

    /*
      Date : 29-08-2021
      Description : Membuat penerimaan barang
      Developer : Didin
      Status : Create
    */
    public static function itemIn($approve_by, $id) {
        self::validateIsRequested($id);
        self::validateIsTakein($id);

        DB::table(self::$table)->whereId($id)
        ->update([
            'status' => self::getInboundStatus(),
            'approve_by' => $approve_by,
            'date_approve' => Carbon::now()
        ]);

        UsingItemDetail::doMultipleInbound($id);
    }
}
