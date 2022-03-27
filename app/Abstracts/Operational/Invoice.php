<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;

class Invoice
{
    protected static $table = 'invoices';

    /*
      Date : 21-04-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function query($params =  []) {
        $request = self::fetchFilter($params);
        $item = DB::table(self::$table)
        ->join('contacts', 'contacts.id', self::$table . '.customer_id')
        ->join('companies', 'companies.id', self::$table . '.company_id')
        ->leftjoin(DB::raw('(select GROUP_CONCAT(distinct job_orders.aju_number SEPARATOR ", ") as aju,GROUP_CONCAT(distinct job_orders.no_bl SEPARATOR ", ") as bl, header_id from invoice_details left join job_orders on job_orders.id = invoice_details.job_order_id group by invoice_details.header_id) as Y'),'Y.header_id','invoices.id');


        // Filter customer, wilayah, status, dan periode
        $tgl_awal = $request['tgl_awal'];
        $tgl_awal = $tgl_awal != null ? preg_replace('/([0-9]+)-([0-9]+)-([0-9]+)/', '$3-$2-$1', $tgl_awal) : '';
        $tgl_akhir = $request['tgl_akhir'];
        $tgl_akhir = $tgl_akhir != null ? preg_replace('/([0-9]+)-([0-9]+)-([0-9]+)/', '$3-$2-$1', $tgl_akhir) : '';
        $item = $tgl_awal != '' && $tgl_akhir != '' ? $item->whereBetween('date_invoice', [$tgl_awal, $tgl_akhir]) : $item;

        $company_id = $request['company_id'];
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;
        $customer_id = $request['customer_id'];
        $customer_id = $customer_id != null ? $customer_id : '';
        $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
        $status = $request['status'];
        $status = $status != null ? $status : '';
        $item = $status != '' ? $item->where('status', $status) : $item;

        if($request['is_sales_order'] == 1) {
            $so = DB::table('invoice_details');
            $so = $so->whereNotNull('job_order_id');
            $so = $so->whereRaw('job_order_id IN (SELECT job_order_id FROM sales_orders)');
            $so = $so->select('invoice_details.header_id');
            $so = $so->toSql();
            $item = $item->whereRaw(self::$table . ".id IN ($so)");
        }

        if($request['is_operational'] == 1) {
            $so = DB::table('invoice_details');
            $so = $so->whereNotNull('job_order_id');
            $so = $so->whereRaw('(cost_type_id IS NULL AND job_order_id IS NULL) OR job_order_id IN (SELECT job_order_id FROM sales_orders)');
            $so = $so->select('invoice_details.header_id');
            $so = $so->toSql();
            $item = $item->whereRaw(self::$table . ".id NOT IN ($so)");
        }

        $item = $item
        ->select(
          'invoices.*',
          'companies.name AS company_name',
          'contacts.name AS customer_name',
          'Y.aju',
          'Y.bl',
          DB::raw("(grand_total+grand_total_additional) as total")
          );

        return $item;
    }

    /*
      Date : 21-04-2021
      Description : Mendapatkan parameter filter data
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($params = []) {
        $request = [];
        $request['customer_id'] = $params['customer_id'] ?? null;
        $request['tgl_awal'] = $params['tgl_awal'] ?? null;
        $request['tgl_akhir'] = $params['tgl_akhir'] ?? null;
        $request['company_id'] = $params['company_id'] ?? null;
        $request['customer_id'] = $params['customer_id'] ?? null;
        $request['status'] = $params['status'] ?? null;
        $request['is_sales_order'] = $params['is_sales_order'] ?? null;
        $request['is_operational'] = $params['is_operational'] ?? null;

        return $request;
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
}
