<?php

namespace App\Abstracts\Sales;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Setting;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Sales\SalesOrderReturnDetail;
use App\Abstracts\Sales\SalesOrderReturnStatus;

class SalesOrderReturn 
{
    protected static $table = 'sales_order_returns';
    public static $type_transaction = 'SalesOrderReturn';
    
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

    public static function validateLimitByWarehouseReceiptDetail($warehouse_receipt_detail_id) {
        $dt = DB::table('sales_order_return_receipts');
        $dt = $dt->where('sales_order_return_receipts.warehouse_receipt_detail_id', $warehouse_receipt_detail_id);
        $dt = $dt->select('sales_order_return_receipts.sales_order_return_id')->first();

        if($dt) {
            self::validateLimit($dt->sales_order_return_id);
        }
    }

    public static function validateLimit($id) {
        $itemSaved = SalesOrderReturnDetail::index($id);
        $itemSaved->each(function($v){
            if($v->received_qty > $v->qty) {
                throw new Exception('Received qty has exceed limit in sales order return');
            }
        });
        self::finish($id);
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['company_id'] = $args['company_id'] ?? null;
        $params['date_transaction'] = $args['date_transaction'] ?? null;
        Checker::checkDate($params['date_transaction']);
        $params['date_transaction'] = Carbon::parse($params['date_transaction'])->format('Y-m-d');
        $params['description'] = $args['description'] ?? null;
        $params['create_by'] = $args['create_by'] ?? null;
        $params['customer_id'] = $args['customer_id'] ?? null;
        Checker::checkContact($params['customer_id']);

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
            throw new Exception('SalesOrderReturn not found');
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
        $dt = $dt->leftJoin('contacts', 'contacts.id', self::$table . '.customer_id');
        $dt = $dt->leftJoin('sales_order_return_statuses', 'sales_order_return_statuses.id', self::$table . '.status');
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(
            self::$table . '.*', 
            'sales_order_return_statuses.name AS status_name', 
            'contacts.name AS customer_name', 
            'companies.name AS company_name'
        );
        
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

        SalesOrderReturnDetail::storeMultiple($params['detail'], $id);

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
            SalesOrderReturnDetail::clear($id);
            SalesOrderReturnDetail::storeMultiple($detail, $id);
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
        SalesOrderReturnDetail::clear($id);
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

        SalesOrderReturnDetail::doMultipleOutbound($id);
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

        SalesOrderReturnDetail::doMultipleInbound($id);
    }

    public static function finish($id) {
        self::validate($id);
        $itemSaved = SalesOrderReturnDetail::index($id);
        $finished = $itemSaved->filter(function ($v){
            return $v->qty == $v->received_qty;
        });

        if(count($itemSaved) > 0) {
            if(count($itemSaved) == count($finished)) {
                DB::table(self::$table)
                ->whereId($id)
                ->update([
                    'status' => SalesOrderReturnStatus::getFinishedStatus()
                ]);
            }
        }
    }
}
