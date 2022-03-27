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
use App\Abstracts\Inventory\PutawayDetail;
use App\Abstracts\Inventory\ItemMigrationType;

class Putaway 
{
    protected static $table = 'item_migrations';
    
    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table(self::$table);

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
        $params['date_transaction'] = $args['date_transaction'] ?? null;
        Checker::checkDate($params['date_transaction']);
        $params['date_transaction'] = Carbon::parse($params['date_transaction'])->format('Y-m-d');
        $params['description'] = $args['description'] ?? null;
        $params['warehouse_from_id'] = $args['warehouse_from_id'] ?? null;
        $params['warehouse_to_id'] = $params['warehouse_from_id'];

        if(!$params['warehouse_from_id']) {
            throw new Exception('Warehouse is required');
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
        $dt = DB::table('item_migrations')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Putaway not found');
        }
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
        $dt = $dt->leftJoin('warehouses AS origin_warehouses', 'origin_warehouses.id', self::$table . '.warehouse_from_id');
        $dt = $dt->leftJoin('warehouse_receipts', 'warehouse_receipts.id', self::$table . '.warehouse_receipt_id')
        ->leftJoin('item_migration_statuses', 'item_migration_statuses.id', self::$table . '.status')
        ->select(self::$table . '.id', self::$table . '.code', self::$table . '.date_transaction',  self::$table . '.date_approve', self::$table . '.status', self::$table . '.description',  'warehouse_receipts.code AS warehouse_receipt_code', 'item_migration_statuses.name AS status_name', 'item_migration_statuses.slug AS status_slug', 'origin_warehouses.name AS warehouse_name');
        $dt = $dt->where(self::$table . '.id', $id);
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
        $warehouse = Warehouse::show($params['warehouse_from_id']);
        $company_id = $warehouse->company_id;
        $code = new TransactionCode($company_id, 'putaway');
        $code->setCode();
        $trx_code = $code->getCode();
        $insert['created_at'] = Carbon::now();
        $insert['code'] = $trx_code;
        $insert['create_by'] = $insert['create_by'] ?? auth()->id();
        $insert['item_migration_type_id'] = ItemMigrationType::getPutaway();
        $id = DB::table(self::$table)->insertGetId($insert);

        PutawayDetail::storeMultiple($params['detail'], $id);

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
        DB::table(self::$table)
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
        PutawayDetail::clearStock($id);
        PutawayDetail::clear($id);
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
    public static function clearStock($job_order_id) {
        $items = self::index($job_order_id)->where('requested_stock_transaction_id', '!=', null)->pluck('requested_stock_transaction_id')->toArray();
        DB::table(self::$table)->whereHeaderId($job_order_id)->update([
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
    public static function itemOut($approve_by, $id) {
        self::validateIsTakeout($id);
        self::validateIsTakein($id);

        DB::table(self::$table)->whereId($id)
        ->update([
            'status' => self::getOutboundStatus(),
            'approve_by' => $approve_by,
            'date_approve' => Carbon::now()
        ]);

        PutawayDetail::doMultipleOutbound($id);
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

        PutawayDetail::doMultipleInbound($id);
    }
}
