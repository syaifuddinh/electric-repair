<?php

namespace App\Abstracts\Sales;

use App\Abstracts\Contact;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Operational\JobOrderContainer;
use App\Abstracts\JobOrder;
use App\Abstracts\JobOrderDetail;
use App\Abstracts\Inventory\Item;
use App\Model\QuotationItem;
use App\Model\SalesOrder as ModelSalesOrder;
use Illuminate\Support\Facades\DB;

class SalesOrder
{
    protected static $table = 'sales_orders';

    /*
      Date : 29-08-2021
      Description : Menampilkan layanan sales order
      Developer : Didin
      Status : Create
    */
    public static function getServiceId() {
        $sales_service = \App\Http\Controllers\Setting\SettingController::fetch('sales_order', 'sales_service_id');
        $sales_service_id = $sales_service->value;
        return $sales_service_id;  
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan sales order
      Developer : Didin
      Status : Create
    */
    /*
      Date : 19-07-2021
      Description : Mengubah query untuk mendapatkan data Sales Order secara detail
      Developer : Hendra
      Status : Edit
    */
    public static function query($request = []) {
        $request = self::fetchFilter($request);

        $dt = DB::table(self::$table)
        ->join('job_orders', 'job_orders.id', self::$table.'.job_order_id')
        ->leftJoin('quotations', 'quotations.id', 'job_orders.quotation_id')
        ->leftJoin('customer_orders', 'customer_orders.id', self::$table.'.customer_order_id')
        ->join('contacts', 'contacts.id', 'job_orders.customer_id')
        ->join('users', 'users.id', 'job_orders.create_by')
        ->leftJoin('users as co_approver', 'co_approver.id', 'customer_orders.approved_by')
        ->leftJoin('contacts as sales', 'sales.id', 'quotations.sales_id')
        ->leftJoin('sales_order_statuses', 'sales_order_statuses.id', 'sales_orders.sales_order_status_id');

        if($request['is_pallet']) {
            $items = JobOrderDetail::query()
            ->join('items', 'items.id', 'job_order_details.item_id')
            ->leftJoin('categories', 'items.category_id', 'categories.id')
            ->leftJoin('categories AS parents', 'parents.id', 'categories.parent_id')
            ->whereRaw("(parents.is_pallet = 1 OR categories.is_pallet = 1)");
            $items = $items->select('job_order_details.header_id');
            $items = $items->toSql();
            $dt = $dt->whereRaw("job_orders.id IN ($items)");
        }

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['is_pallet'] = $args['is_pallet'] ?? null;

        return $params;
    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = self::query()
        ->where(self::$table . '.id', $id)
        ->select(self::$table . '.id', self::$table . '.code', 'contacts.name AS customer_name', 'job_orders.shipment_date', 'job_orders.created_at', self::$table . '.job_order_id', 'job_orders.customer_id', 'job_orders.total_price', 'quotations.no_contract', 'job_orders.company_id', 'job_orders.invoice_id', 'users.name as created_by_name', 'co_approver.name as co_approver_name', 'sales.name as sales_name', 'sales_order_statuses.id as status_id', 'sales_order_statuses.name as status', 'sales_order_statuses.slug as status_slug', 'customer_orders.payment_type')
          ->first();

        return $dt;
    }

    /*
      Date : 19-04-2021
      Description : Menampilkan data berdasarkan job order
      Developer : Didin
      Status : Create
    */    
    public static function showByJobOrder($job_order_id) {
        $dt = DB::table(self::$table);
        $dt->whereJobOrderId($job_order_id);
        $dt = $dt->first();


        return $dt;
    }

    /*
      Date : 19-04-2021
      Description : Memvalidasi apakah terdapat sales order berdasarkan parameter job order
      Developer : Didin
      Status : Create
    */    
    public static function hasJobOrder($job_order_id) {
        $dt = DB::table(self::$table);
        $dt->whereJobOrderId($job_order_id);
        $dt = $dt->count('id');

        if($dt > 0) {
            return true;
        } else {
            return false;
        }

    }

    /*
      Date : 07-03-2021
      Description : Menghitung harga berdasarkan job order 
      Developer : Didin
      Status : Create
    */
    public static function countPriceByJobOrder($job_order_id) {
        $dt = self::showByJobOrder($job_order_id);
        // dd($dt);
        if($dt) {
            self::countPrice($dt->id);
        }
    }

    /*
      Date : 07-03-2021
      Description : Menghitung harga 
      Developer : Didin
      Status : Create
    */
    /*
      Date : 15-07-2021
      Description : Mengganti harga berdasarkan kontrak (jika ada)
      Developer : Hendra
      Status : Edit
    */
    public static function countPrice($id) {
        $dt = self::show($id);
        $jo = JobOrder::show($dt->job_order_id);
        $details = JobOrderDetail::index($dt->job_order_id);
        // $details = $details->where('warehouse_receipt_detail_id', '=', null);
        // dd($details);
        $details->each(function($value)use($jo){
          $item = Item::show($value->item_id);
          $price = $item->harga_jual;
          if($jo->quotation_id){
            $itemQuo = QuotationItem::where('quotation_id', $jo->quotation_id)
                                  ->where('item_id', $value->item_id)
                                  ->first();
            if($itemQuo){
              $price = $itemQuo->price;
            }
          }
          JobOrderDetail::updatePrice($value->id, $price, 3);
        });

        $total_price = 0;
        $newDetails = JobOrderDetail::index($dt->job_order_id);
        $total_price += $newDetails->sum('total_price');
        // dd($newDetails, $total_price);

        DB::table('job_orders')->where('id', $dt->job_order_id)->update(['total_price' => $total_price]);
    }

    /*
      Date : 22-07-2021
      Description : Approve SO
      Developer : Hendra
      Status : Create
    */
    public static function approve($id) {
        self::validate($id);
        $status = DB::table('sales_order_statuses')->where('slug', 'approved')->first();
        if(!$status){
          throw new Exception('Status approve tidak ditemukan!', 500);
        }
        DB::table(self::$table)
        ->whereId($id)
        ->update(['sales_order_status_id' => $status->id]);
    }
    
    /*
      Date : 22-07-2021
      Description : Reject SO
      Developer : Hendra
      Status : Create
    */
    public static function reject($id) {
        self::validate($id);
        $status = DB::table('sales_order_statuses')->where('slug', 'rejected')->first();
        if(!$status){
          throw new Exception('Status reject tidak ditemukan!', 500);
        }
        DB::table(self::$table)
        ->whereId($id)
        ->update(['sales_order_status_id' => $status->id]);
    }

    /*
      Date : 07-03-2021
      Description : Menghapus data
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
      Date : 22-07-2021
      Description : Memvalidasi limit piutang ketika input SO
      Developer : Hendra
      Status : Create
    */
    public static function validasiLimitPiutang($id) {
      $dt = self::show($id);
      $total_price = $dt->total_price;
      $resp = Contact::validasiLimitPiutang($dt->customer_id, $total_price, $getResp= true);
      return $resp;
  }
}
