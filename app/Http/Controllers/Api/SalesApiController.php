<?php

namespace App\Http\Controllers\Api;

use App\Abstracts\Sales\CustomerOrder;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Sales\SalesOrder;
use App\Abstracts\Sales\SalesOrderDetail;
use Illuminate\Support\Facades\DB;
use Response;
use Yajra\DataTables\Facades\DataTables;

class SalesApiController extends Controller
{
    /*
      Date : 16-03-2020
      Description : Menampilkan sales order
      Developer : Didin
      Status : Edit
    */
    public function sales_order_datatable(Request $request)
    {
        $dt = SalesOrder::query($request->all());

        if($request->filled('customer_id')) {
            $dt->where('job_orders.customer_id', $request->customer_id);
        }

        if($request->filled('start_date') && $request->filled('end_date')){
            if(dateDB($request->start_date) > dateDB($request->end_date)){
                $temp = $request->start_date;
                $request->start_date = $request->end_date;
                $request->end_date = $temp;
            }
        }

        if($request->filled('start_date')){
            $dt->whereRaw('DATE(job_orders.shipment_date) >= "' . dateDB($request->start_date) .'"');
        }

        if($request->filled('end_date')){
            $dt->whereRaw('DATE(job_orders.shipment_date) <= "' . dateDB($request->end_date) .'"');
        }

        if($request->filled('is_invoiced')){
            if($request->is_invoiced == true){
                $dt->whereNotNull('invoice_id');
            } else {
                $dt->whereNull('invoice_id');
            }
        }
        if($request->filled('for_invoicing')){
            $dt->whereIn('sales_order_statuses.slug', ['waiting_for_payment', 'approved']);
            $dt->whereNull('invoice_id');
        }

        $dt = $dt->select('sales_orders.id', 'sales_orders.code', 'contacts.name AS customer_name', 'job_orders.shipment_date', 'sales_order_statuses.name as status');

        return DataTables::of($dt)
        ->make(true);
    }

    /*
      Date : 12-07-2021
      Description : Menampilkan list detail sales order
      Developer : Hendra
      Status : Create
    */
    public function sales_order_detail_datatable(Request $request)
    {
        $dt = SalesOrderDetail::query($request->all());

        if($request->filled('sales_order_id')){
            $dt = $dt->where('sales_orders.id', $request->sales_order_id);
        }

        return DataTables::of($dt)
                        ->make(true);
    }

    /*
      Date : 06-07-2021
      Description : Menampilkan list customer order
      Developer : Hendra
      Status : Create
    */
    public function customer_order_datatable(Request $request)
    {
        $dt = CustomerOrder::query($request->all());

        if($request->filled('customer_id')) {
            $dt->where('customer_orders.customer_id', $request->customer_id);
        }

        if($request->filled('start_date') && $request->filled('end_date')){
            if(dateDB($request->start_date) > dateDB($request->end_date)){
                $temp = $request->start_date;
                $request->start_date = $request->end_date;
                $request->end_date = $temp;
            }
        }

        if($request->filled('start_date')){
            $dt->whereRaw('DATE(customer_orders.date) >= "' . dateDB($request->start_date) .'"');
        }

        if($request->filled('end_date')){
            $dt->whereRaw('DATE(customer_orders.date) <= "' . dateDB($request->end_date) .'"');
        }

        $dt = $dt->select('customer_orders.id', 'customer_orders.code', 'contacts.name AS customer_name', DB::raw('DATE(customer_orders.date) as date'), 'quotations.no_contract', 'customer_order_statuses.name as status', 'customer_orders.created_at');

        return DataTables::of($dt)
        ->editColumn('date', function($row){
            return fullDate($row->date);
        })
        ->make(true);
    }
}
