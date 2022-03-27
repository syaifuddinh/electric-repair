<?php

namespace App\Abstracts\Sales;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Setting;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Sales\SalesOrderReturn;
use App\Abstracts\Sales\SalesOrderReturnReceipt;
use App\Abstracts\JobOrderDetail;

class SalesOrderReturnDetail
{
    protected static $table = 'sales_order_return_details';

    public static function receiptQuery() {
        $dt = SalesOrderReturnReceipt::query();
        $dt = $dt->groupBy('sales_order_return_receipts.sales_order_return_detail_id');
        $dt = $dt->select(
            'sales_order_return_receipts.sales_order_return_detail_id',
            DB::raw("SUM(warehouse_receipt_details.qty) AS qty")
        ); 
        return $dt;
    }
    
    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('items','items.id', self::$table . '.item_id');
        $dt = $dt->leftJoin('categories','categories.id','items.category_id');
        $dt = $dt->leftJoin('job_order_details','job_order_details.id', self::$table . '.job_order_detail_id');
        $dt = $dt->leftJoin('job_orders','job_orders.id', 'job_order_details.header_id');
        $dt = $dt->leftJoin('sales_orders','job_orders.id', 'sales_orders.job_order_id');

        $request = self::fetchFilter($params);
        if($request['header_id']) {
            $dt = $dt->where(self::$table . '.header_id', $request['header_id']);
        }

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['header_id'] = $args['header_id'] ?? null;

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan daftar nama item condition
      Developer : Didin
      Status : Create
    */
    public static function index($sales_order_return_id) {
        $params = [];
        if($sales_order_return_id) {
            $params['header_id'] = $sales_order_return_id;
        }
        $dt = self::query($params);
        $receipts = self::receiptQuery();
        $dt = $dt->leftJoinSub($receipts, "receipts", function($q){
            $q->on('receipts.sales_order_return_detail_id', 'sales_order_return_details.id');
        });
        $dt = $dt->selectRaw('
        sales_order_return_details.*,
        items.name,
        items.barcode,
        items.code,
        COALESCE(receipts.qty, 0) AS received_qty,
        sales_orders.code AS sales_order_code,
        job_order_details.qty AS qty_in_sales,
        categories.name as category_name');
        
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
        $params['job_order_detail_id'] = $args['job_order_detail_id'] ?? null;
        $params['qty'] = $args['qty'] ?? 0;
        $params['updated_at'] = Carbon::now();

        if($params['job_order_detail_id']) {
            $jod = JobOrderDetail::show($params['job_order_detail_id']);
            $params['item_id'] = $jod->item_id;
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
            throw new Exception('Sales Order Return detail not found');
        }
    }

    /*
      Date : 05-03-2021
      Description : Menghapus semua data
      Developer : Didin
      Status : Create
    */
    public static function clear($sales_order_return_id) {
        SalesOrderReturn::validate($sales_order_return_id);
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.header_id', $sales_order_return_id);
        $dt->delete();
    }


    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function storeMultiple($details, $sales_order_return_id) {
        if(is_array($details)) {
            self::clear($sales_order_return_id);
            foreach($details as $detail) {
                $detail = (array) $detail;
                self::store($detail, $sales_order_return_id);
            }
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
        
        $dt = $dt->where(self::$table . '.id', $id);
        
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail berdasarkan ID barang dan ID Sales Order Return
      Developer : Didin
      Status : Create
    */
    public static function showByItem($sales_order_return_id, $item_id) {
        $dt = DB::table(self::$table);
        
        $dt = $dt->where(self::$table . '.header_id', $sales_order_return_id);
        $dt = $dt->where(self::$table . '.item_id', $item_id);
        
        $dt = $dt->first();

        return $dt;
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params, $id) {
        $insert = self::fetch($params);
        self::validateIsExceed($insert['job_order_detail_id'], $insert['qty']);
        $insert['header_id'] = $id;
        $insert['created_at'] = Carbon::now();
        $id = DB::table(self::$table)->insertGetId($insert);

        return $id;
    }

    /*
      Date : 23-03-2021
      Description : Memvalidasi apakah jumlah barang melebihi jumlah di sales order
      Developer : Didin
      Status : Create
    */

    public static function validateIsExceed($job_order_detail_id, $qty, $id = null) {
        $dt = DB::table(self::$table);
        $dt = $dt->whereJobOrderDetailId($job_order_detail_id);
        if($id) {
            $dt = $dt->where(self::$table . '.id', '!=', $id);
        }
        $return = $dt->sum('qty') + $qty;
        $jod = JobOrderDetail::show($job_order_detail_id);
        $inSales = $jod->qty;
        if($return > $inSales) {
            throw new Exception('Qty return limit of ' . $jod->item_name . ' was exceeded');
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
    public static function clearStock($sales_order_return_id) {
        $items = self::index($sales_order_return_id)->where('requested_stock_transaction_id', '!=', null)->pluck('requested_stock_transaction_id')->toArray();
        DB::table(self::$table)->whereHeaderId($sales_order_return_id)->update([
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
}
