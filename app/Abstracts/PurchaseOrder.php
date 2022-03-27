<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Inventory\PurchaseOrderStatus;
use App\Abstracts\Journal;
use App\Abstracts\Contact;

class PurchaseOrder
{
    protected static $table = 'purchase_orders';
    
    /*
      Date : 29-08-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($args = []) {
        $params = [];
        $params['po_status'] = $args['po_status'] ?? null;
        $params['vehicle_maintenance_isnull'] = $args['vehicle_maintenance_isnull'] ?? null;
        $params['company_id'] = $args['company_id'] ?? null;
        $params['supplier_id'] = $args['supplier_id'] ?? null;
        $params['status'] = $args['status'] ?? null;
        $params['is_pallet'] = $args['is_pallet'] ?? null;
        $params['is_merchandise'] = $args['is_merchandise'] ?? null;
        $params['start_date'] = $args['start_date'] ?? null;
        $params['end_date'] = $args['end_date'] ?? null;
        $params['is_approved'] = $args['is_approved'] ?? null;

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi limit hutang supplier
      Developer : Didin
      Status : Create
    */
    public static function validasiLimitHutang($id) {
        $dt = self::show($id);
        $total_price = PurchaseOrderDetail::getTotalPrice($id);
        Contact::validasiLimitHutang($dt->supplier_id, $total_price);

    }

    /*
      Date : 29-08-2021
      Description : Meng-query purchase order
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $request = self::fetchFilter($params);
        $wr="1=1";
        if ($request['po_status']) {
            $wr.=" and purchase_orders.po_status = " . $request['po_status'];
            if($request['po_status'] == 1) {
                $wr.=" or receipts.status = 1";
            }
        }
        if ($request['vehicle_maintenance_isnull']) {
          $wr.=" and purchase_orders.vehicle_maintenance_id IS NULL";
        }

        $purchase_request = DB::raw('(SELECT id, is_pallet FROM purchase_requests WHERE is_pallet = 0) AS purchase_requests');

        $item = DB::table('purchase_orders')
            ->leftJoin('purchase_order_statuses', 'purchase_order_statuses.id', self::$table . '.status')
            ->leftJoin('warehouses', 'warehouses.id', self::$table . '.warehouse_id')
            ->join("contacts", "contacts.id", "purchase_orders.supplier_id")
            ->join("companies", "companies.id", "purchase_orders.company_id")
            ->leftJoin($purchase_request,'purchase_requests.id','purchase_orders.purchase_request_id')
            ->leftJoin('receipts','po_id','purchase_orders.id')
            ->whereRaw($wr);

        $start_date = $request['start_date'];
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request['end_date'];
        $end_date = $end_date != null ? new DateTime($end_date) : '';

        if($start_date) {
            $item = $item->where('po_date', '>=', $start_date);
        }


        if($end_date) {
            $item = $item->where('po_date', '<=', $end_date);
        }

        $company_id = $request['company_id'];
        $item = $company_id != '' ? $item->where(DB::raw('purchase_orders.company_id'), $company_id) : $item;

        $supplier_id = $request['supplier_id'];
        $item = $supplier_id != '' ? $item->where(DB::raw('purchase_orders.supplier_id'), $supplier_id) : $item;

        $status = $request['status'];
        $item = $status != '' ? $item->where(self::$table . '.status', $status) : $item;


        if($request['is_pallet'] == 1) {
            $item = $item->whereRaw('purchase_orders.id IN (SELECT purchase_order_details.header_id FROM purchase_order_details JOIN items ON items.id = purchase_order_details.item_id JOIN categories ON categories.id = items.category_id LEFT JOIN categories AS parents ON parents.id = categories.parent_id WHERE categories.is_pallet = 1 OR parents.is_pallet = 1)');
        }

        if($request['is_merchandise'] == 1) {
            $item = $item->whereRaw('purchase_orders.id IN (SELECT purchase_order_details.header_id FROM purchase_order_details JOIN items ON items.id = purchase_order_details.item_id WHERE items.is_merchandise = 1)');
        }

        if($request['is_approved'] == 1) {
            $approvedStatus = PurchaseOrderStatus::getApproved();
            $item = $item->where(self::$table . '.status', $approvedStatus);
        }

        $item = $item->select(
            'purchase_orders.*', 
            DB::raw('receipts.status AS receipt_status'), 
            'warehouses.name AS warehouse_name', 
            'contacts.name AS supplier_name', 
            'companies.name AS company_name',
            'purchase_order_statuses.name  AS status_name'
        );

        return $item;
    }
    
    /*
      Date : 29-08-2021
      Description : Menangkap parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($params = []) {
        $args = [];
        $args['company_id'] = $params['company_id'] ?? null;
        $args['supplier_id'] = $params['supplier_id'] ?? null;
        $args['warehouse_id'] = $params['warehouse_id'] ?? null;
        $args['po_date'] = $params['po_date'] ?? null;
        $args['po_date'] = Carbon::parse($args['po_date'])->format('Y-m-d');
        $args['po_by'] = $params['po_by'] ?? auth()->id();
        $args['description'] = $params['description'] ?? null;
        $args['payment_type'] = $params['payment_type'] ?? 1;
        $args['purchase_request_id'] = $params['purchase_request_id'] ?? null;
        $dt = $args;
        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Meng-nampilkan daftar order
      Developer : Didin
      Status : Create
    */
    public static function index($keyword = null) {
        $dt = self::query();
        if($keyword) {
            $dt = $dt->where('purchase_orders', 'like', "%$keyword%");
        }

        $dt = $dt->select('purchase_orders.id', 'purchase_orders.code');
        $dt = $dt->get();

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan purchase order
      Developer : Didin
      Status : Create
    */
    public static function store($params = []) {
        $args = self::fetch($params);
        $detail = $params['detail'] ?? [];
        if($args['company_id']) {
            $code = new TransactionCode($args['company_id'], 'purchaseOrder');
            $code->setCode();
            $trx_code = $code->getCode();
            $args['code'] = $trx_code;
        }
        $args['created_at'] = Carbon::now();
        $args['status'] = PurchaseOrderStatus::getRequested();

        $id = DB::table('purchase_orders')
        ->insertGetId($args);
        PurchaseOrderDetail::storeMultiple($detail, $id);

        return $id;
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan purchase order
      Developer : Didin
      Status : Create
    */
    public static function update($params = [], $id) {
        $args = self::fetch($params);
        $po = self::show($id);
        if($po->company_id != ($args['company_id'] ?? null)) {
            if($args['company_id']) {
                $code = new TransactionCode($args['company_id'], 'purchaseOrder');
                $code->setCode();
                $trx_code = $code->getCode();
                $args['code'] = $trx_code;
            }
        }
        $id = DB::table('purchase_orders')
        ->whereId($id)
        ->update($args);

        return $id;
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail sales order
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        $dt = self::query();
        $dt = $dt->where('purchase_orders.id', $id)
          ->first();

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menghapus sales order
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validateWasApproved($id);
        PurchaseOrderDetail::clear($id);
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }

    /*
      Date : 29-08-2021
      Description : Validasi data apakah sudah di-approve
      Developer : Didin
      Status : Create
    */
    public static function validateWasApproved($id) {
        $dt = self::show($id);
        if($dt->status == PurchaseOrderStatus::getApproved()) {
            throw new Exception('Data was approved');
        }
    }

    /*
      Date : 29-08-2021
      Description : Approve data
      Developer : Didin
      Status : Create
    */
    public static function approve($id) {
        self::validateWasApproved($id);
        self::validasiLimitHutang($id);
        DB::table(self::$table)->whereId($id)->update([
            'status' => PurchaseOrderStatus::getApproved()
        ]);
        Journal::setJournal(14, $id);
    }

    /*
      Date : 02-06-2021
      Description : Menyelesaikan transaksi penerimaan barang
      Developer : Didin
      Status : Create
    */
    public static function finishReceipt($id) {
        $items = PurchaseOrderDetail::index($id);
        $finished = true;
        foreach ($items as $i) {
            if($i->received_qty < $i->qty) {
                $finished = false;
                break;
            }
        }
        if($finished) {
            DB::table(self::$table)->whereId($id)->update([
                'status' => PurchaseOrderStatus::getFinished()
            ]);
        }
    }
}
