<?php
namespace App\Http\Controllers\Api\v4;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\VoyageSchedule;
use App\Model\Container;
use App\Model\JobOrder;
use App\Model\WorkOrder;
use App\Model\Manifest;
use App\Model\Invoice;
use App\Model\DeliveryOrderDriver;
use App\Model\JobOrderCost;
use App\Model\KpiLog;
use App\Model\InvoiceVendor;
use Carbon\Carbon;
use DataTables;
use DB;
use Response;
use DateTime;
use Curl;

class OperationalApiController extends Controller
{
    public function job_order_datatable(Request $request)
    {
        $wr="1=1";
        if (isset($request->is_operational_done)) {
            $wr.=" AND job_orders.is_operational_done = ".$request->is_operational_done;
        }

        if (isset($request->is_handling)) {
          $wr.=" AND job_orders.is_handling = ".$request->is_handling;
        }
        if (isset($request->is_stuffing)) {
          $wr.=" AND job_orders.is_stuffing = ".$request->is_stuffing;
        }
        if (isset($request->is_warehouserent)) {
          $wr.=" AND job_orders.is_warehouserent = ".$request->is_warehouserent;
        }
        if (isset($request->is_packaging)) {
          $wr.=" AND job_orders.is_packaging = ".$request->is_packaging;
        }

        $item = JobOrder::with('customer:id,name','service:id,name','kpi_status:id,name')
            ->leftJoin('services','services.id','job_orders.service_id')
            ->leftJoin('service_types', 'service_types.id', '=', 'services.service_type_id')
            ->leftJoin('kpi_statuses','kpi_statuses.id','=','job_orders.kpi_id')
            ->leftJoin('quotations','quotations.id','=','job_orders.quotation_id')
            ->leftJoin('work_orders','work_order_id','=','work_orders.id')
            ->whereRaw($wr)
            ->select('job_orders.id', 'job_orders.code', 'job_orders.shipment_date', 'job_orders.customer_id', 'job_orders.service_id', 'job_orders.kpi_id', 'job_orders.no_bl')->orderBy('job_orders.id', 'DESC')->orderBy('shipment_date', 'DESC');

        if(isset($request->show_invoice)) {
          $item = $item->with('invoice_jual:job_order_id,header_id', 'invoice_jual.invoice:id,code');
        }    
        
    return DataTables::of($item)
      ->filter(function($query) use($request) {
            $wr="1=1";
            if (isset($request->kpi_status_name)) {
              $wr.=" AND kpi_statuses.name = '".$request->kpi_status_name . "'";
            }
            if (isset($request->service_id)) {
              $wr.=" AND job_orders.service_id = ".$request->service_id;
            }
            
            if (isset($request->kpi_id)) {
              $wr.=" AND job_orders.kpi_id = ".$request->kpi_id;
            }
            if (isset($request->collectible_id)) {
              $wr.=" AND job_orders.collectible_id = $request->collectible_id";
            }
            if (isset($request->not_invoice)) {
              $wr.=" AND job_orders.invoice_id is null";
            }
            if (isset($request->customer_id)) {
              $wr.=" AND job_orders.customer_id = ".$request->customer_id;
            }
            
            if (isset($request->service_not_in)) {
              foreach ($request->service_not_in as $key => $value) {
                $wr.=" AND job_orders.service_type_id != ".$value;
              }
            }
            if (isset($request->is_done)) {
              $wr.=" AND kpi_statuses.is_done = $request->is_done";
            }
            if ($request->company_id) {
              $wr.=" AND job_orders.company_id = $request->company_id";
            } else {
              if (auth()->user()->is_admin==0) {
                $wr.=" AND job_orders.company_id = ".auth()->user()->company_id;
              }
            }
            if ($request->exclude_borongan) {
              $wr.=" and IF(job_orders.quotation_id is not null, quotations.bill_type=1, 1=1)";
            }
            // if ($request->jo_list_append) {
            //   $wr.=" AND job_orders.id not in ($request->jo_list_append)";
            // }
            if(!isset($request->start_date)) {
              $request->start_date = "1945-08-07";
            }
            if(!isset($request->end_date)) {
              $request->end_date = "2145-08-07";
            }

            if (isset($request->start_date) || isset($request->end_date)) {
              if (isset($request->start_date)) {
                $start = dateDB($request->start_date);
                if (isset($request->end_date)){
                  $end = dateDB($request->end_date);
                }else {
                  $end = $start;
                }
              }else if (isset($request->end_date)){
                $end = dateDB($request->end_date);
                $start = $end;
              }
              $wr.=" AND (shipment_date BETWEEN '".$start."' AND '".$end."')";
            }

            $query->whereRaw($wr)->limit($request->length ?? 1000000)->offset($request->start ?? 0);
      })
      ->editColumn('no_bl', function($item){
        $str="";
        $explode=explode(',',$item->no_bl);
        foreach ($explode as $key => $value) {
          $str.="$value";
        }
        return $str;
      })
      ->editColumn('shipment_date', function($item){
        return dateView($item->shipment_date);
      })
      ->make(true);
    // return $dt->toJson();
    }

    public function manifest_ftl_datatable(Request $request)
    {
        $op = new \App\Http\Controllers\Api\OperationalApiController();
        return $op->manifest_ftl_datatable($request); 
    }

    public function stuffing_datatable(Request $request)
    {
        $request->source = 'picking_order';
        $dt = $this->manifest_ftl_datatable($request);
        return $dt; 
    }

    public function crossdocking_datatable(Request $request)
    {
        $request->is_crossdocking = 1;
        $dt = $this->manifest_ftl_datatable($request);
        return $dt; 
    }
}
