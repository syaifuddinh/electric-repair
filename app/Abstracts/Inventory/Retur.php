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
use App\Abstracts\Inventory\ReturDetail;
use App\Abstracts\Inventory\ReturStatus;
use App\Abstracts\Inventory\ReturType;

class Retur 
{
    protected static $table = 'returs';
    public static $type_transaction = 'retur';
    
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
        $params['supplier_id'] = $args['supplier_id'] ?? null;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['create_by'] = $args['create_by'] ?? null;

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
            throw new Exception('Retur not found');
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
        $dt = $dt->leftJoin('contacts', 'contacts.id', self::$table . '.supplier_id');
        $dt = $dt->leftJoin('retur_statuses', 'retur_statuses.id', self::$table . '.status');
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(self::$table . '.*', 'warehouses.name AS warehouse_name', 'companies.name AS company_name', 'retur_statuses.name AS status_name', 'contacts.name AS supplier_name');
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
        $code = new TransactionCode($company_id, 'retur');
        $code->setCode();
        $trx_code = $code->getCode();
        $insert['created_at'] = Carbon::now();
        $insert['code'] = $trx_code;
        $insert['status'] = ReturStatus::getDraft();
        $insert['type_retur'] = ReturType::getItem();
        $id = DB::table(self::$table)->insertGetId($insert);

        ReturDetail::storeMultiple($params['detail'], $id);

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
        $detail = $params ['detail'] ?? null;
        $update = self::fetch($params);

        DB::table(self::$table)
        ->whereId($id)
        ->update($update);

        if($detail && is_array($detail)) {
            ReturDetail::clearStock($id);
            ReturDetail::clear($id);

            ReturDetail::storeMultiple($detail, $id);
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

        ReturDetail::doMultipleOutbound($id);
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

        ReturDetail::doMultipleInbound($id);
    }

    /*
      Date : 23-03-2021
      Description : Memvalidasi apakah data sudah dikeluarkan atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsApproved($id) {
        $dt = self::show($id);
        $approveStatus = ReturStatus::getApproved();
        if($dt->status == $approveStatus) {
            throw new Exception('Data was approved');
        }
    }

    /*
      Date : 29-08-2021
      Description : Membuat pengeluaran barang
      Developer : Didin
      Status : Create
    */
    public static function approve($id) {
        self::validateIsApproved($id);

        DB::table(self::$table)->whereId($id)
        ->update([
            'status' => ReturStatus::getApproved(),
            'updated_at' => Carbon::now()
        ]);

        UsingItemDetail::doMultipleOutbound($id);
    }
}
