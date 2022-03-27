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
use App\Abstracts\Inventory\ItemMigrationDetail;
use App\Abstracts\Inventory\ItemMigrationType;

class ItemMigration 
{
    protected static $table = 'item_migrations';
    public static $type_transaction = 'itemMigration';
    
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
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Item migration not found');
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
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->first();

        return $dt;
    }

    public static function showByWarehouseReceipt($warehouse_receipt_id) {
        $r = null;
        $receipt = DB::table('item_migration_receipts')
        ->whereWarehouseReceiptId($warehouse_receipt_id)
        ->first();

        if($receipt) {
            $r = self::show($receipt->item_migration_id);
        }

        return $r;
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
        $code = new TransactionCode($company_id, self::$type_transaction);
        $code->setCode();
        $trx_code = $code->getCode();
        $insert['created_at'] = Carbon::now();
        $insert['code'] = $trx_code;
        $insert['create_by'] = $insert['create_by'] ?? auth()->id();
        $insert['item_migration_type_id'] = ItemMigrationType::getItemMigration();
        $id = DB::table(self::$table)->insertGetId($insert);

        ItemMigrationDetail::storeMultiple($params['detail'], $id);

        return $id;
    }
    
    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params, $id) {
        self::validateIsTakeout($id);
        self::validate($id);
        $detail = $params ['detail'] ?? null;
        $update = self::fetch($params);

        DB::table(self::$table)
        ->whereId($id)
        ->update($update);

        if($detail && is_array($detail)) {
            ItemMigrationDetail::clearStock($id);
            ItemMigrationDetail::clear($id);

            ItemMigrationDetail::storeMultiple($detail, $id);
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

        DB::table(self::$table)->whereId($id)
        ->update([
            'status' => self::getOutboundStatus(),
            'approve_by' => $approve_by,
            'date_approve' => Carbon::now()
        ]);

        ItemMigrationDetail::doMultipleOutbound($id);
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

        ItemMigrationDetail::doMultipleInbound($id);
    }

    public static function getWarehouses($id) {
        $dt = self::show($id);
        $details = ItemMigrationDetail::index($id);
        $warehouses = $details->where("destination_warehouse_id", '!=', null)->pluck("destination_warehouse_id")->toArray();
        $warehouses[] = $dt->warehouse_from_id;

        return $warehouses;
    } 

    public static function getItems($id) {
        self::validate($id);
        $details = ItemMigrationDetail::index($id);
        $items = $details->pluck("item_id")->toArray();

        return $items;
    } 

    /*
      Date : 02-06-2021
      Description : Menyelesaikan transaksi penerimaan barang
      Developer : Didin
      Status : Create
    */
    public static function finishReceipt($id) {
        self::validateIsTakein($id);
        $items = ItemMigrationDetail::index($id);
        $finished = true;
        foreach ($items as $i) {
            if($i->received_qty < $i->qty) {
                $finished = false;
                break;
            }
        }
        if($finished) {
            DB::table(self::$table)->whereId($id)->update([
                'status' => self::getInboundStatus()
            ]);
        }
    }   
}
