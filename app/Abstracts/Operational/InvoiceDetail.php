<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;

class InvoiceDetail
{
    /*
      Date : 16-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('invoice_details');
        $dt = $dt->leftJoin('job_orders', 'job_orders.id', 'invoice_details.job_order_id');
        $dt = $dt->leftJoin('work_orders', 'work_orders.id', 'job_orders.work_order_id');

        return $dt;
    }

    /*
      Date : 16-03-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('invoice_details')
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 12-02-2021
      Description : Mengisi layanan pada detail invoice, function ini dibutuhkan jika invoice mengambil dari paket work order
      Developer : Didin
      Status : Create
    */
    public static function setServiceId($invoice_id = null) {
        $dt = self::query();
        $dt = $dt->whereNull('invoice_details.cost_type_id');
        $dt = $dt->whereNotNull('invoice_details.job_order_id');
        $dt = $dt->where('work_orders.is_job_packet', 1);
        if($invoice_id) {
            $dt = $dt->where('invoice_details.header_id', $invoice_id);
        }
        $dt = $dt->join('services', 'services.name', 'invoice_details.commodity_name');
        $dt = $dt->select('invoice_details.id', 'services.id AS service_id');
        $dt = $dt->get();
        foreach($dt as $item) {
            DB::table('invoice_details')
            ->whereId($item->id)
            ->update([
                'service_id' => $item->service_id
            ]);
        }
    }
}
