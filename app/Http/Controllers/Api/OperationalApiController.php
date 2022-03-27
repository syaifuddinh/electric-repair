<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\VoyageSchedule;
use App\Model\Container;
use App\Model\JobOrder;
use App\Model\WorkOrder;
use App\Model\Manifest;
use App\Abstracts\Operational\Invoice;
use App\Model\DeliveryOrderDriver;
use App\Abstracts\Operational\DeliveryOrderDriver AS DOD;
use App\Model\JobOrderCost;
use App\Model\KpiLog;
use App\Model\InvoiceVendor;
use App\Abstracts\JobOrder AS JO;
use App\Abstracts\AdditionalField;
use Carbon\Carbon;
use Response;
use DateTime;
use Curl;
use Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class OperationalApiController extends Controller
{
  public function voyage_schedule_datatable(Request $request)
  {
        return DataTables::of(self::voyage_schedule_query($request))
        ->filterColumn('total', function($query, $keyword) {
            $sql = "Y.total like ?";
            $query->whereRaw($sql, ["%{$keyword}%"]);
        })
        ->make(true);
  }

  /*
      Date : 31-03-2020
      Description : Menampilkan daftar kontainer dalam format datatable
      Developer : Didin
      Status : Edit
    */
  public function container_datatable(Request $request)
  {
    return DataTables::of(self::container_query($request))
      ->addColumn('action_choose', function($item){
        $html="<button ng-click='chooseContainer($item->id,\"$item->container_no\")' class='btn btn-xs btn-success'>Pilih</button>";
        return $html;
      })
      ->editColumn('is_fcl', function($item){
        $stt=[
          1=>'FCL',
          0=>'LCL',
        ];
        return $stt[$item->is_fcl];
      })
      ->rawColumns(['action_choose'])
      ->make(true);
  }
  public function job_order_datatable(Request $request)
  {
    $wr="1=1";
    if (isset($request->kpi_status_name)) {
      $wr.=" AND kpi_statuses.name = '".$request->kpi_status_name . "'";
    }
    if (isset($request->service_id)) {
      $wr.=" AND job_orders.service_id = ".$request->service_id;
    }
    if (isset($request->is_operational_done)) {
      $wr.=" AND job_orders.is_operational_done = ".$request->is_operational_done;
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

    $user = Auth::user();
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
    if($user->is_admin == 0 && $user->contact != null && (isset($request->is_handling) || isset($request->is_stuffing)  || isset($request->is_warehouserent) || isset($request->is_packaging) ) ) {
        if($user->contact->is_staff_gudang == 1) {
            $wr.=" AND kpi_statuses.is_done = 0";
        }
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
    if ($request->filled('start_date') || $request->filled('end_date')) {
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
      $wr.=" AND shipment_date BETWEEN '".$start."' AND '".$end."'";
    }
    $item = DB::table('job_orders')
        ->leftJoin('companies','companies.id','job_orders.company_id')
        ->leftJoin('contacts','contacts.id','job_orders.customer_id')
        ->leftJoin('contacts AS receivers','receivers.id','job_orders.receiver_id')
        ->leftJoin('contacts AS senders','senders.id','job_orders.sender_id')
        ->leftJoin('services','services.id','job_orders.service_id')
        ->leftJoin('routes','routes.id','job_orders.route_id')
        ->leftJoin('service_types', 'service_types.id', '=', 'services.service_type_id')
        ->leftJoin('kpi_statuses','kpi_statuses.id','=','job_orders.kpi_id')
        ->leftJoin('quotations','quotations.id','=','job_orders.quotation_id')
        ->leftJoin('work_orders','work_order_id','=','work_orders.id')
        ->whereRaw($wr)
        ->select('job_orders.*', 'companies.name AS company_name', 'contacts.name AS customer_name', 'routes.name AS route_name', 'receivers.name AS receiver_name', 'senders.name AS sender_name', 'services.name AS service_name', 'service_types.name AS service_type_name', 'kpi_statuses.name AS kpi_status_name');

    $item = $item->whereRaw('job_orders.id NOT IN (SELECT job_order_id FROM sales_orders WHERE job_order_id IS NOT NULL)');

    if($request->is_depo_service == 1) {
        $item = $item->whereIn('services.service_type_id', [7, 12, 13, 15]);
    }

    if(isset($request->show_invoice)) {
      if($request->show_invoice == 0) {
          $item->whereNull('job_orders.invoice_id');
      } else {
        $item = $item->leftJoin('invoices', 'invoices.id', 'job_orders.invoice_id')
        ->whereNotNull('job_orders.invoice_id');
      }
    }

    $item->whereRaw('job_orders.id NOT IN (SELECT job_order_id FROM job_packets)');

    $params = [];
    $params['show_in_index'] = 1;
    $additionalFields = AdditionalField::indexKey('jobOrder', $params);
    if(count($additionalFields) > 0) {
        $addon = '';
        foreach ($additionalFields as $a) {
            $addon .= ', ';
            $addon .= "REPLACE(JSON_EXTRACT(job_orders.additional, '$.$a'), '\"', '') AS $a";
        }
        $additionals = "(SELECT id $addon FROM job_orders) AS additional_job_orders";
        $item = $item->leftJoin(DB::raw($additionals), 'additional_job_orders.id', 'job_orders.id');
        foreach ($additionalFields as $a) {
            $item = $item->addSelect(['additional_job_orders.' . $a]);
        }
    }

    return DataTables::of($item)
      ->addColumn('action_customer', function($item){
        $html="<a ui-sref='main.job_order.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->addColumn('action_vendor', function($item) use ($request) {
        if($request->user()->hasRole('vendor.job_order.detail'))
            return "<a ui-sref='main.job_order.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return '';
      })
      ->addColumn('action_choose', function($item){
        $html="<a ng-click='selectJO($item->id,\"$item->code\")' class='btn btn-xs btn-success'>Pilih</a>";
        return $html;
      })
      ->addColumn('checklist', function($item){
        return "<div class='checkbox checkbox-primary checkbox-inline' ng-show='roleList.includes(\"operational.job_order.operasional_selesai\")'><input ng-change='isCheck()' type='checkbox' ng-model='checkData.detail[".$item->id."].value' ng-true-value='1' ng-false-value='0'><label for='tr-".$item->id."'></label></div>";
      })
      ->editColumn('no_bl', function($item){
        $str="";
        $explode=explode(',',$item->no_bl);
        foreach ($explode as $key => $value) {
          $str.="$value<br>";
        }
        return $str;
      })
      ->editColumn('aju_number', function($item){
        $str="";
        $explode=explode(',',$item->aju_number);
        foreach ($explode as $key => $value) {
          $str.="$value<br>";
        }
        return $str;
      })
      ->rawColumns(['checklist','action_choose','action_customer','no_bl','aju_number'])
      ->make(true);
  }

    /*
      Date : 10-07-2021
      Description : Menampilkan daftar job order detail datatable
      Developer : Hendra
      Status : Create
    */
    public function job_order_detail_datatable(Request $request)
    {
        $dt = DB::table('job_order_details')
        ->leftJoin('commodities', 'commodities.id', 'job_order_details.commodity_id')
        ->select(
            'job_order_details.id',
            'commodities.name AS commodity_name',
            'job_order_details.commodity_id',
            'job_order_details.price',
            'job_order_details.qty',
            'job_order_details.description',
            'job_order_details.total_price'
        );

        if($request->filled('job_order_id')) {
            $dt = $dt->where('job_order_details.header_id', $request->job_order_id);
        }

        return DataTables::of($dt)
        ->make(true);
    }

  public function vendor_job_datatable(Request $request)
  {
    $manifest_details = DB::raw('(SELECT job_order_details.header_id AS job_order_id, manifest_details.header_id AS manifest_id FROM manifest_details JOIN job_order_details ON job_order_details.id = manifest_details.job_order_detail_id GROUP BY manifest_details.header_id) AS manifest_details');
    $manifest = DB::table('manifest_costs') 
    ->leftJoin('vendor_job_statuses', 'vendor_job_statuses.id', 'manifest_costs.vendor_job_status_id')
    ->join('contacts AS vendors', 'vendors.id', 'manifest_costs.vendor_id')
    ->join('manifests', 'manifests.id', 'manifest_costs.header_id')
    ->join($manifest_details, 'manifest_details.manifest_id', 'manifests.id')
    ->join('job_orders', 'job_orders.id', 'manifest_details.job_order_id')
    ->join('contacts', 'contacts.id', 'job_orders.customer_id')
    ->join('companies', 'companies.id', 'manifests.company_id')
    ->join('cost_types', 'cost_types.id', 'manifest_costs.cost_type_id')
    ->selectRaw('manifest_costs.id, "Manifest" AS source_name, "manifest" AS source, companies.name AS company_name, contacts.name AS customer_name, manifests.code, cost_types.name AS cost_type_name, manifest_costs.qty, manifest_costs.price, manifest_costs.total_price, vendor_job_statuses.name AS vendor_job_status_name, vendor_job_statuses.id AS vendor_job_status_id, vendors.name AS vendor_name');

    if($request->filled('customer_id')) {
        $manifest->where('job_orders.customer_id', $request->customer_id);
    }

    if($request->filled('vendor_id')) {
        $manifest->where('manifest_costs.vendor_id', $request->vendor_id);
    }

    $item = DB::table('job_order_costs') 
    ->join('job_orders', 'job_orders.id', 'job_order_costs.header_id')
    ->join('contacts AS vendors', 'vendors.id', 'job_order_costs.vendor_id')
    ->leftJoin('vendor_job_statuses', 'vendor_job_statuses.id', 'job_order_costs.vendor_job_status_id') 
    ->join('contacts', 'contacts.id', 'job_orders.customer_id')
    ->join('companies', 'companies.id', 'job_orders.company_id')
    ->join('cost_types', 'cost_types.id', 'job_order_costs.cost_type_id')
    ->selectRaw('job_order_costs.id, "Job Order" AS source_name, "job_order" AS source, companies.name AS company_name, contacts.name AS customer_name, job_orders.code, cost_types.name AS cost_type_name, job_order_costs.qty, job_order_costs.price, job_order_costs.total_price, vendor_job_statuses.name AS vendor_job_status_name, vendor_job_statuses.id AS vendor_job_status_id, vendors.name AS vendor_name');
    if($request->filled('customer_id')) {
        $item->where('job_orders.customer_id', $request->customer_id);
    }

    if($request->filled('vendor_id')) {
        $item->where('job_order_costs.vendor_id', $request->vendor_id);
    }
    $item = $item->union($manifest);

    $item = $item->get()->sortByDesc('id');
    return DataTables::of($item)
    ->make(true);
  }



  public function jo_datatable(Request $request)
  {

     // dd($request);
    $wr="1=1";
    if (isset($request->kpi_status_name)) {
      $wr.=" AND kpi_statuses.name = '".$request->kpi_status_name . "'";
    }
    if (isset($request->service_id)) {
      $wr.=" AND job_orders.service_id = ".$request->service_id;
    }
    if (isset($request->is_operational_done)) {
      $wr.=" AND job_orders.is_operational_done = ".$request->is_operational_done;
    }
    if (isset($request->kpi_id)) {
      $wr.=" AND job_orders.kpi_id = ".$request->kpi_id;
    }
    if (isset($request->collectible_id)) {
      $wr.=" AND job_orders.collectible_id = $request->collectible_id";
    }
    if ($request->not_invoice) {
      $wr.=" AND job_orders.invoice_id is null";
    }
    if ($request->is_invoice) {
      $wr.=" AND job_orders.invoice_id is not null";
    }
    if (isset($request->customer_id)) {
      $wr.=" AND job_orders.customer_id = ".$request->customer_id;
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
    if ($request->jo_list_append) {
      $wr.=" AND job_orders.id not in ($request->jo_list_append)";
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
      $wr.=" AND job_orders.shipment_date BETWEEN '".$start."' AND '".$end."'";
    }
    $item=DB::table('job_orders')
        ->leftJoin('quotations','quotations.id','job_orders.quotation_id')
        ->leftJoin('contacts','contacts.id','job_orders.customer_id')
        ->leftJoin('routes','routes.id','job_orders.route_id')
        ->leftJoin('services','services.id','job_orders.service_id')
        ->leftJoin('service_types', 'service_types.id', '=', 'services.service_type_id')
        ->leftJoin('kpi_statuses','kpi_statuses.id','job_orders.kpi_id')
        ->leftJoin('work_orders','work_orders.id','job_orders.work_order_id')
        ->leftJoin('companies','work_orders.company_id','companies.id')
        ->leftJoin('contacts as receiver','receiver.id','job_orders.receiver_id')
        ->leftJoin('contacts as sender','sender.id','job_orders.sender_id')
        ->whereRaw($wr)
        ->selectRaw('
        job_orders.*,
        contacts.name as customer,
        services.name as service,
        routes.name as trayek,
        work_orders.code as wo_code,
        kpi_statuses.name as kpi_status,
        service_types.name as service_type,
        sender.name as sender_name,
        receiver.name as receiver_name,
        concat(services.name,\' (\',service_types.name,\')\') service_full,
        companies.name as company_name
        ')->orderBy('job_orders.id', 'DESC')->orderBy('shipment_date', 'DESC');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational.job_order.detail')\" ui-sref='operational.job_order.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if (empty($item->invoice_id)) {
          $html.="<a ng-show=\"roleList.includes('operational.job_order.edit')\" ui-sref='operational.job_order.edit({id:$item->id})' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('operational.job_order.delete')\" ng-click='deletes($item->id)' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->addColumn('action_customer', function($item){
        $html="<a ui-sref='main.job_order.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html="<a ng-click='selectJO($item->id,\"$item->code\")' class='btn btn-xs btn-success'>Pilih</a>";
        return $html;
      })
      ->addColumn('checklist', function($item){
        return "<div class='checkbox checkbox-primary checkbox-inline'><input ng-change='isCheck()' type='checkbox' ng-model='checkData.detail[".$item->id."].value' ng-true-value='1' ng-false-value='0' id='tr-{$item->id}'><label for='tr-".$item->id."'></label></div>";
      })
      ->editColumn('no_bl', function($item){
        $str="";
        $explode=explode(',',$item->no_bl);
        foreach ($explode as $key => $value) {
          $str.="$value<br>";
        }
        return $str;
      })
      ->editColumn('aju_number', function($item){
        $str="";
        $explode=explode(',',$item->aju_number);
        foreach ($explode as $key => $value) {
          $str.="$value<br>";
        }
        return $str;
      })
      ->editColumn('shipment_date', function($item){
        return dateView($item->shipment_date);
      })
      ->rawColumns(['action','checklist','action_choose','action_customer','no_bl','aju_number'])
      ->make(true);
    return $dt->toJson();
  }
  public function job_order_inProgress(Request $request)
  {

    $month = new Carbon(date('Y-m-d', strtotime($request->date)));
    $startPeriode = $month->copy()->startOfMonth()->format('Y-m-d');
    $endPeriode = $month->copy()->endOfMonth()->format('Y-m-d');
    // kpi_statuses where is done 0
    $jo=DB::table('job_orders')
    ->leftJoin('kpi_statuses','kpi_statuses.id','job_orders.kpi_id')
    ->selectRaw("
    sum(if(kpi_statuses.is_done=1,1,0)) as total_done,
    sum(if(kpi_statuses.is_done=0,1,0)) as total_process
    ")
    ->whereBetween('job_orders.shipment_date', [$startPeriode, $endPeriode])->first();

    $response['data'] = $jo;
    return Response::json($response,200,[],JSON_NUMERIC_CHECK);
  }
  public function invoice_jual_amount(Request $request)
  {
    $month = new Carbon(date('Y-m-d', strtotime($request->date)));
    $startPeriode = $month->copy()->startOfMonth()->format('Y-m-d');
    $endPeriode = $month->copy()->endOfMonth()->format('Y-m-d');
    $period=Carbon::now()->format('Y-m');
    // invoice = 3, terbayar sebagian = 4, lunas = 5

    $unpaid = Invoice::whereIn('status', [3])->whereBetween('date_invoice', [$startPeriode, $endPeriode]);
    $all=DB::table('invoices')->whereRaw("date_format(date_invoice, '%Y-%m') = '$period'")->selectRaw('
    count(id) as total_invoice,
    ifnull(sum(grand_total),0) as grand_total
    ')->first();

    $unpaid = DB::table('receivables')
    ->leftJoin('invoices','invoices.id','receivables.relation_id')
    ->whereRaw("receivables.type_transaction_id = 26 and date_format(invoices.date_invoice, '%Y-%m') = '$period' and (receivables.debet-receivables.credit) > 0")
    ->selectRaw('
    count(invoices.id) as total_invoice,
    ifnull(sum(debet-credit),0) as grand_total
    ')->first();
    $response['data'] = [
      'all' => [
        'count' => $all->total_invoice,
        'summary' => $all->grand_total
      ],
      'unpaid' => [
        'count' => $unpaid->total_invoice,
        'summary' => $unpaid->grand_total
      ],
    ];

    return Response::json($response, 200,[],JSON_NUMERIC_CHECK);
  }
    public function invoice_jual_datatable(Request $request)
    {

        $item = Invoice::query($request->all());
        if (auth()->user()->is_admin==0) {
            $item = $item->where('invoices.company_id', auth()->user()->company_id);
        }

        return DataTables::of($item)
          ->addColumn('action_customer', function($item){
            $html="<a ui-sref='main.invoice.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            return $html;
          })
          ->editColumn('created_at', function($item){
            return dateView($item->created_at);
          })
          ->editColumn('total', function($item){
            return formatNumber($item->total);
          })
          ->filterColumn('aju', function($query, $keyword) {
              $sql = "Y.aju like ?";
              $query->whereRaw($sql, ["%{$keyword}%"]);
            })
          ->filterColumn('bl', function($query, $keyword) {
              $sql = "Y.bl like ?";
              $query->whereRaw($sql, ["%{$keyword}%"]);
            })
          ->filterColumn('total', function($query, $keyword) {
              $sql = "(grand_total+grand_total_additional) like ?";
              $query->whereRaw($sql, ["%{$keyword}%"]);
              })
          ->addColumn('status_name', function($item){
            $stt=[
              1=>'Diajukan',
              2=>'Disetujui',
              3=>'Invoice',
              4=>'Terbayar Sebagian',
              5=>'Lunas',
            ];
            return $stt[$item->status];
          })
          ->rawColumns(['action','action_customer','aju'])
          ->toJson();
    }
    public function manifest_ftl_datatable(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND manifests.company_id = ".auth()->user()->company_id;
        }

        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND manifests.company_id = ".auth()->user()->company_id;
        }
        if ($request->status) {
            $wr.=" and dod.job_status_id = ".$request->status;
        }

        if ($request->start_date && $request->end_date) {
            $start = Carbon::parse($request->start_date)->format('Y-m-d');
            $end = Carbon::parse($request->end_date)->format('Y-m-d');
            $wr.=" AND date(manifests.date_manifest) between '$start' and '$end'";
        }
        
        $wr.=" and manifests.is_container = 0";

        $item=DB::table('manifests')
        ->leftJoin('containers','containers.id','manifests.container_id')
        ->leftJoin('voyage_schedules','voyage_schedules.id','containers.voyage_schedule_id')
        ->leftJoin('companies','companies.id','manifests.company_id')
        ->leftJoin('routes','routes.id','manifests.route_id')
        ->leftJoin('delivery_order_drivers as dod', function($join){
          $join->on('dod.manifest_id', '=','manifests.id');
          $join->where('dod.status', '<', 3);
        })
        ->leftJoin('job_statuses','job_statuses.id','dod.job_status_id')
        ->leftJoin('contacts as driver','driver.id','dod.driver_id')
        ->leftJoin('vehicles','vehicles.id','dod.vehicle_id')
        ->leftJoin(DB::raw('(select count(*) as total_item, header_id from manifest_details group by header_id) as md'),'md.header_id','manifests.id')
        ->whereRaw($wr)
        ->selectRaw('
        manifests.id,
        manifests.code,
        manifests.date_manifest,
        manifests.created_at,
        if(dod.driver_id is not null,driver.name, dod.driver_name) as sopir,
        if(dod.vehicle_id is not null,vehicles.nopol, dod.nopol) as kendaraan,
        routes.name as trayek,
        companies.name as company,
        job_statuses.name as job_status,
        dod.code as code_sj,
        ifnull(containers.container_no,manifests.container_no) as container_no,
        voyage_schedules.voyage,
        md.total_item,
        if(manifests.is_full=1,\'FTL\',\'LTL\') as tipe_angkut
        ');

        $params = [];
        $params['show_in_index'] = 1;
        $additionalFields = AdditionalField::indexKey('manifest', $params);
        if(count($additionalFields) > 0) {
            $addon = '';
            foreach ($additionalFields as $a) {
                $addon .= ', ';
                $addon .= "REPLACE(JSON_EXTRACT(manifests.additional, '$.$a'), '\"', '') AS $a";
            }
            $additionals = "(SELECT id $addon FROM manifests) AS additional_manifests";
            $item = $item->leftJoin(DB::raw($additionals), 'additional_manifests.id', 'manifests.id');
            foreach ($additionalFields as $a) {
                $item = $item->addSelect(['additional_manifests.' . $a]);
            }
        }

    if($request->filled('company_id')) {
        $item->where('manifests.company_id', $request->company_id);
    }

    if($request->source) {
        $item->where('manifests.source', $request->source);
    }

    if($request->is_crossdocking) {
        $item->where('manifests.is_crossdocking', $request->is_crossdocking);
    }

    if($request->sales_order_id) {
      $item->leftJoin('manifest_details','manifest_details.header_id','manifests.id');
      $item->leftJoin('job_order_details as jod','manifest_details.job_order_detail_id','jod.id');
      $item->leftJoin('sales_orders as so','so.job_order_id','jod.header_id');
      $item->where('so.id', $request->sales_order_id);
      $item->groupBy(DB::raw('id, code, date_manifest, created_at, sopir, kendaraan, trayek, company, job_status, code_sj,
      container_no, voyage, total_item, tipe_angkut'));
    }

    return DataTables::of($item)
      ->filterColumn('tipe_angkut', function($query, $keyword) {
        $sql="if(manifests.is_full=1,'FTL','LTL') like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->editColumn('job_status', function($item){
        if (!$item->job_status) {
          return "Draft";
        }
        return $item->job_status;
      })
      ->make(true);
  }
  public function manifest_fcl_datatable(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND manifests.company_id = ".auth()->user()->company_id;
    }
    if ($request->status) {
      $wr.=" and dod.job_status_id = ".$request->status;
    }
    if ($request->start_date && $request->end_date) {
      $start = Carbon::parse($request->start_date)->format('Y-m-d');
      $end = Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" AND date(manifests.date_manifest) between '$start' and '$end'";
    }
    $wr.=" and manifests.is_container = 1";

    $item=DB::table('manifests')
    ->leftJoin('containers','containers.id','manifests.container_id')
    ->leftJoin('voyage_schedules','voyage_schedules.id','containers.voyage_schedule_id')
    ->leftJoin('companies','companies.id','manifests.company_id')
    ->leftJoin('routes','routes.id','manifests.route_id')
    ->leftJoin('delivery_order_drivers as dod','dod.manifest_id','manifests.id')
    ->leftJoin('job_statuses','job_statuses.id','dod.job_status_id')
    ->leftJoin('contacts as driver','driver.id','dod.driver_id')
    ->leftJoin('vehicles','vehicles.id','dod.vehicle_id')
    ->leftJoin(DB::raw('(select count(*) as total_item, header_id from manifest_details group by header_id) as md'),'md.header_id','manifests.id')
    ->whereRaw($wr)
    ->selectRaw('
    manifests.id,
    manifests.code,
    manifests.date_manifest,
    manifests.created_at,
    if(dod.driver_id is not null,driver.name, dod.driver_name) as sopir,
    if(dod.vehicle_id is not null,vehicles.nopol, dod.nopol) as kendaraan,
    routes.name as trayek,
    companies.name as company,
    job_statuses.name as job_status,
    dod.code as code_sj,
    containers.container_no,
    voyage_schedules.voyage,
    md.total_item,
    if(manifests.is_full=1,\'FCL\',\'LCL\') as tipe_angkut
    ');

    $params = [];
    $params['show_in_index'] = 1;
    $additionalFields = AdditionalField::indexKey('manifest', $params);
    if(count($additionalFields) > 0) {
        $addon = '';
        foreach ($additionalFields as $a) {
            $addon .= ', ';
            $addon .= "REPLACE(JSON_EXTRACT(manifests.additional, '$.$a'), '\"', '') AS $a";
        }
        $additionals = "(SELECT id $addon FROM manifests) AS additional_manifests";
        $item = $item->leftJoin(DB::raw($additionals), 'additional_manifests.id', 'manifests.id');
        foreach ($additionalFields as $a) {
            $item = $item->addSelect(['additional_manifests.' . $a]);
        }
    }
    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational.manifest.container.detail')\" ui-sref='operational.manifest_fcl.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->total_item<1) {
          $html.="<a ng-show=\"roleList.includes('operational.manifest.container.delete')\" ng-click='deletes($item->id)' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->filterColumn('tipe_angkut', function($query, $keyword) {
        $sql="if(manifests.is_full=1,'FCL','LCL') like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->editColumn('job_status', function($item){
        if (!$item->job_status) {
          return "Manifest";
        }
        return $item->job_status;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
    public function delivery_order_driver_datatable(Request $request)
    {

        $item = DOD::query($request->all());

        $item = $item->selectRaw('
        delivery_order_drivers.*,
        manifests.code as code_pl,
        driver.name as driver,
        vehicles.nopol,
        routes.name as trayek,
        job_statuses.name as status_name,
        if(delivery_order_drivers.driver_id is not null,driver.name, delivery_order_drivers.driver_name) as sopir,
        if(delivery_order_drivers.vehicle_id is not null,vehicles.nopol, delivery_order_drivers.nopol) as kendaraan
        ');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('operational.delivery_order.detail')\" ui-sref='operational.delivery_order_driver.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            return $html;
        })
        ->editColumn('pick_date', function($item){
            return dateView($item->pick_date);
        })
        ->editColumn('status', function($item){
            $stt=[
              1=>'Ditugaskan',
              2=>'Selesai',
            ];
            return $stt[$item->status] ?? '';
        })
        ->editColumn('is_finish', function($item){
            $stt=[
              1=>'<span class="badge badge-primary">Finished</span>',
              0=>'<span class="badge badge-warning">Ongoing</span>',
            ];
            return $stt[$item->is_finish];
        })
        ->rawColumns(['action','is_finish'])
        ->make(true);
    }

  /*
      Date : 24-03-2020
      Description : Menampilkan daftar shipment status dalam
                    format datatable
      Developer : Didin
      Status : Edit
  */
  public function shipmentStatusDatatable(Request $request)
  {
    $this->updateShipmentTerkirim();
    $this->updateShipmentSampai();
    $this->updateShipmentSelesai();
    $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
    $end_date = Carbon::parse($request->end_date)->format('Y-m-d');
    $item = DB::table('warehouse_receipts AS W')
    ->join('warehouse_receipt_details AS WD', 'WD.header_id', 'W.id')
    ->join('contacts AS C', 'C.id', 'W.customer_id')
    ->join(DB::raw("(SELECT warehouse_receipt_id, MAX(`status`) AS `status` FROM shipment_statuses GROUP BY warehouse_receipt_id) AS S"), 'S.warehouse_receipt_id', 'W.id')
    ->leftJoin('job_order_details AS JD', 'JD.warehouse_receipt_detail_id', 'WD.id')
    ->leftJoin('job_orders AS J', 'J.id', 'JD.header_id')
    ->whereRaw("DATE_FORMAT(W.receive_date, '%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'")
    ->groupBy('W.id')
    ->select(
      'W.id',
      'W.code',
      'C.name AS customer_name',
      'S.status',
      'W.receive_date',
      DB::raw('GROUP_CONCAT(J.code SEPARATOR ",") AS job_order_code'),
      DB::raw('SUM(WD.qty) AS qty'),
      DB::raw('SUM(WD.long * WD.wide * WD.high * WD.qty / 1000000) AS volume'),
      DB::raw('SUM(WD.weight * WD.qty) AS weight')
    );
    return DataTables::of($item)
      ->make(true);
  }

  /*
      Date : 24-03-2020
      Description : Memeriksa KPI Status yang sudah selesai
      Developer : Didin
      Status : Create
  */
  public function updateShipmentSelesai() {
      // Periksa FTL
      $job_orders = DB::table('warehouse_receipts AS W')
      ->join('warehouse_receipt_details AS WD', 'WD.header_id', 'W.id')
      ->join('job_order_details AS JD', 'JD.warehouse_receipt_detail_id', 'WD.id')
      ->join('job_orders AS J', 'J.id', 'JD.header_id')
      ->join('kpi_statuses AS K', 'J.kpi_id', 'K.id')
      ->where('K.is_done', 1)
      ->groupBy('J.id')
      ->select('W.id')
      ->get();

      foreach ($job_orders as $job_order) {
          $latest_shipment = DB::table('shipment_statuses')
          ->whereWarehouseReceiptId($job_order->id)
          ->whereStatus(4)
          ->count('id');

          if($latest_shipment < 1) {
              DB::table('shipment_statuses')
              ->insert([
                'warehouse_receipt_id' => $job_order->id,
                'status_date' => DB::raw('DATE_FORMAT(NOW(), "%Y-%m-%d")'),
                'status' => 4
              ]);
          }
      }
  }

  /*
      Date : 24-03-2020
      Description : Memeriksa manifest yang sudah terkirim
      Developer : Didin
      Status : Create
  */
  public function updateShipmentTerkirim() {
      // Periksa FTL
      $job_orders = DB::table('warehouse_receipts AS W')
      ->join('warehouse_receipt_details AS WD', 'WD.header_id', 'W.id')
      ->join('job_order_details AS JD', 'JD.warehouse_receipt_detail_id', 'WD.id')
      ->join('manifest_details AS MD', 'MD.job_order_detail_id', 'JD.id')
      ->join('manifests AS M', 'M.id', 'MD.header_id')
      ->select('W.id', 'M.depart', 'M.container_id', 'M.is_container')
      ->get();

      foreach ($job_orders as $job_order) {
          $now = Carbon::now();
          if($job_order->is_container == 0 && $job_order->depart != null) {
              $depart = Carbon::parse($job_order->depart);
              if($now->gt($depart) OR $depart->eq($now)) {
                  $latest_shipment = DB::table('shipment_statuses')
                  ->whereWarehouseReceiptId($job_order->id)
                  ->whereStatus(2)
                  ->count('id');
              }
          } else if($job_order->is_container == 1) {
              $container = DB::table('containers')
              ->whereId($job_order->container_id)
              ->first();
              if(($container->stripping ?? null) != null) {
                  $stripping = Carbon::parse($container->stripping);
                  if($now->gt($stripping) OR $stripping->eq($now)) {
                      $latest_shipment = DB::table('shipment_statuses')
                      ->whereWarehouseReceiptId($job_order->id)
                      ->whereStatus(2)
                      ->count('id');
                  }
              }
          }

          if(($latest_shipment ?? 2) < 1) {
              DB::table('shipment_statuses')
              ->insert([
                'warehouse_receipt_id' => $job_order->id,
                'status_date' => DB::raw('DATE_FORMAT(NOW(), "%Y-%m-%d")'),
                'status' => 2
              ]);
          }
      }

  }

  /*
      Date : 24-03-2020
      Description : Memeriksa manifest yang sudah sampai
      Developer : Didin
      Status : Create
  */

  public function updateShipmentSampai() {
      // Periksa FTL
      $job_orders = DB::table('warehouse_receipts AS W')
      ->join('warehouse_receipt_details AS WD', 'WD.header_id', 'W.id')
      ->join('job_order_details AS JD', 'JD.warehouse_receipt_detail_id', 'WD.id')
      ->join('manifest_details AS MD', 'MD.job_order_detail_id', 'JD.id')
      ->join('manifests AS M', 'M.id', 'MD.header_id')
      ->select('W.id', 'M.arrive', 'M.container_id', 'M.is_container')
      ->get();

      foreach ($job_orders as $job_order) {
          $now = Carbon::now();
          if($job_order->is_container == 0 && $job_order->arrive != null) {
              $arrive = Carbon::parse($job_order->arrive);
              if($now->gt($arrive) OR $arrive->eq($now)) {
                  $latest_shipment = DB::table('shipment_statuses')
                  ->whereWarehouseReceiptId($job_order->id)
                  ->whereStatus(3)
                  ->count('id');
              }
          } else if($job_order->is_container == 1) {
              $container = DB::table('containers')
              ->whereId($job_order->container_id)
              ->first();
              if(($container->stuffing ?? null) != null) {
                  $stuffing = Carbon::parse($container->stuffing);
                  if($now->gt($stuffing) OR $stuffing->eq($now)) {
                      $latest_shipment = DB::table('shipment_statuses')
                      ->whereWarehouseReceiptId($job_order->id)
                      ->whereStatus(3)
                      ->count('id');
                  }
              }
          }


          if(($latest_shipment ?? 2) < 1) {
              DB::table('shipment_statuses')
              ->insert([
                'warehouse_receipt_id' => $job_order->id,
                'status_date' => DB::raw('DATE_FORMAT(NOW(), "%Y-%m-%d")'),
                'status' => 3
              ]);
          }
      }

  }


  /*
      Date : 17-03-2020
      Description : Menampilkan daftar biaya job order dalam format
                    datatable
      Developer : Didin
      Status : Edit
  */
  public function job_order_cost_datatable(Request $request)
  {
    $wr="1=1 and job_order_costs.vendor_id is not null and job_order_costs.header_id is not null";
    if ($request->status) {
      $wr.=" and job_order_costs.status = {$request->status}";
    }
    if ($request->start_date && $request->end_date) {
      $start = Carbon::parse($request->start_date)->format('Y-m-d');
      $end = Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and date(job_order_costs.created_at) between '$start' and '$end'";
    }
    $item = DB::table('job_order_costs')
    ->leftJoin('contacts','contacts.id','job_order_costs.vendor_id')
    ->leftJoin('job_orders','job_orders.id','job_order_costs.header_id')
    ->leftJoin('cost_types','cost_types.id','job_order_costs.cost_type_id')
    ->whereRaw($wr)
    ->selectRaw('
      job_order_costs.*,
      contacts.name as vendor,
      cost_types.name as cost_type,
      cost_types.akun_kas_hutang AS account_id,
      job_orders.code,
      job_orders.shipment_date
    ');

    if($request->filled('cost_type')) {
        $item->where('cost_types.type', $request->cost_type);
    }
    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ui-sref='operational.job_order.show({id:".$item->header_id."})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>";
        return $html;
      })
      ->addColumn('action_vendor', function($item) use($request) {
        if($request->user()->hasRole('vendor.job_order.detail'))
            return "<a ui-sref='operational.job_order.show({id:".$item->header_id."})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return '';
      })
      ->editColumn('status', function($item){
        $stt=[
          1=>'Belum Diajukan',
          2=>'Diajukan Keuangan',
          3=>'Disetujui Keuangan',
          4=>'Ditolak',
          5=>'Diposting',
          6=>'Revisi',
          7=>'Diajukan',
          8=>'Disetujui Atasan',
        ];
        return $stt[$item->status];
      })
      ->editColumn('created_at', function($item){
        return Carbon::parse($item->created_at)->format('d-m-Y');
      })
      ->rawColumns(['action','action_vendor','status'])
      ->make(true);
  }


  /*
      Date : 03-03-2020
      Description : Menampilkan daftar biaya manifest dalam format
                    datatable
      Developer : Didin
      Status : Create
    */
  public function manifest_cost_datatable(Request $request)
  {
    $item = DB::table('manifest_costs')
    ->leftJoin('contacts','contacts.id','manifest_costs.vendor_id')
    ->leftJoin('manifests','manifests.id','manifest_costs.header_id')
    ->leftJoin('cost_types','cost_types.id','manifest_costs.cost_type_id')
    ->selectRaw('
      manifest_costs.*,
      contacts.name as vendor,
      manifests.code,
      cost_types.akun_kas_hutang as account_id,
      cost_types.name as cost_type
    ');

    if($request->filled('cost_type')) {
        $item->where('cost_types.type', $request->cost_type);
    }
    return DataTables::of($item)
      ->make(true);
  }
  public function invoice_vendor_datatable(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND invoice_vendors.company_id = ".auth()->user()->company_id;
    }

    $item = InvoiceVendor::with('vendor','company')->whereRaw($wr);
    $start_date = $request->start_date;
    $start_date = $start_date != null ? new DateTime($start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? new DateTime($end_date) : '';
    $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_invoice', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

    $company_id = $request->company_id;
    $company_id = $company_id != null ? $company_id : '';
    $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;

    $vendor_id = $request->vendor_id;
    $vendor_id = $vendor_id != null ? $vendor_id : '';
    $item = $vendor_id != '' ? $item->where('vendor_id', $vendor_id) : $item;

    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status', $status) : $item;
    $item = $item->select('invoice_vendors.*');

    if($request->draw == 1) {
        $item = $item->orderBy('invoice_vendors.id', 'DESC');
    }

    return DataTables::of($item)
      // ->addColumn('action', function($item){
      //   $html="<a ng-show=\"roleList.includes('operational.invoice_vendor.detail')\" ui-sref='operational.invoice_vendor.show({id:".$item->id."})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>";
      //   return $html;
      // })
      ->editColumn('status', function($item){
        $stt=[
          1=>'Belum Lunas',
          2=>'Lunas',
        ];
        return $stt[$item->status];
      })
      // ->editColumn('status_approve', function($item){
      //   $stt=[
      //     0=>'<span class="badge badge-warning">Belum Disetujui</span>',
      //     1=>'<span class="badge badge-primary">Disetujui</span>',
      //     2=>'<span class="badge badge-success">Jurnal disetujui</span>',
      //     4=>'<span class="badge badge-success">Hutang telah dibuat</span>',
      //   ];
      //   return $stt[$item->status_approve];
      // })
      ->editColumn('total', function($item){
        return formatNumber($item->total);
      })
      ->rawColumns(['status'])
      ->make(true);
  }
  public function kpi_log_datatable(Request $request)
  {
    JO::setNullableAdditionals();
    $wr="kpi_logs.id in (select max(kpi_logs.id) from kpi_logs group by kpi_logs.job_order_id)";
    $wr_jo="1=1";
    $params=$request->params;
    if (($params['customer_id']??false)) {
      $wr_jo.=" AND customer_id = ".$params['customer_id'];
    }
    if (($params['job_order']??false)) {
      $txt=$params['job_order'];
      $wr_jo.=" AND code LIKE '%$txt%'";
    }
    if (($params['create_by']??false)) {
      $wr.=" AND create_by = ".$params['create_by'];
    }
    if (($params['service']??false)) {
      $wr_jo.=" AND service_id = ".$params['service'];
    }
    if (($params['start_date']??false) && ($params['end_date']??false)) {
      $start=Carbon::parse($params['start_date'])->format('Y-m-d');
      $end=Carbon::parse($params['end_date'])->format('Y-m-d');
      $wr.=" AND date(date_update) between '$start' AND '$end'";
    }
    if (auth()->user()->is_admin==0) {
      $wr_jo.=" AND company_id = ".auth()->user()->company_id;
    }
    //$wr.=" AND (job_orders.kpi_id = kpi_logs.kpi_status_id)";
    $item = KpiLog::with('job_order','job_order.service','job_order.customer','creates','kpi_status')
    ->whereHas('job_order', function($query) use ($wr_jo){
      $query->whereRaw($wr_jo);
    })
    ->leftJoin('job_orders','job_orders.id','kpi_logs.job_order_id')
    ->whereRaw($wr)
    ->selectRaw('kpi_logs.*');

    $params = [];
    $params['show_in_operational_progress'] = 1;
    $jobOrderAdditional = AdditionalField::indexKey('jobOrder', $params);
    $additionalColumn = '';
    if(count($jobOrderAdditional) > 0) {
        foreach ($jobOrderAdditional as $i => $v) {
            $item = $item->addSelect([DB::raw("REPLACE(JSON_EXTRACT(job_orders.additional, '$.$v'), '\"', '')  AS $v")]);
        }
    }
    //->groupBy('kpi_logs.job_order_id');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational.progress.detail')\" ui-sref='operational.job_order.show({id:".$item->job_order_id."})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-click='edit(".json_encode($item,JSON_HEX_APOS).")' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        if (isset($item->file_name)) {
          $html.='<a ng-show="roleList.includes(\'operational.progress.detail\')" download="docs_'.$item->job_order_id.'_'.$item->id.'" href="'.url($item->file_name).'"><i class="fa fa-file"></i></a>';
        }
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function map_driver_job_list(Request $request)
  {
    $driverOn=DB::table('contacts')
    ->leftJoin('vehicles','vehicles.id','contacts.vehicle_id')
    ->whereRaw("contacts.is_driver = 1 and contacts.is_login = 1")
    ->selectRaw('
    contacts.id,
    contacts.name,
    contacts.lat,
    contacts.lng,
    vehicles.nopol,
    contacts.updated_at as last_update
    ')->get();
    $job=DB::table('delivery_order_drivers as dod')
    ->leftJoin('vehicles','vehicles.id','dod.vehicle_id')
    ->leftJoin('contacts','contacts.id','dod.driver_id')
    ->leftJoin('job_statuses','job_statuses.id','dod.job_status_id')
    ->whereRaw('date(dod.updated_at) = date(now())')
    ->selectRaw('
    contacts.id as driver_id,
    contacts.name as driver,
    vehicles.nopol,
    job_statuses.name as status,
    dod.code as no_sj,
    dod.is_finish,
    dod.updated_at as last_update
    ')
    ->orderBy('last_update','desc')->get();
    $data=[
      'driver' => $driverOn,
      'job' => $job
    ];
    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }

  public function get_last_position_by_vendor_1(Request $request)
  {
    $params=[
      'token' => '16D90D94C567488',
      'all_vehicle' => 1
    ];
    $url="http://vts.easygo-gps.co.id/api/get_vts_last_position.aspx".'?'.http_build_query($params);

    $get=Curl::to($url)
        ->asJson()
        ->post();
        // dd($get);
    $data=[];
    foreach ($get->data as $key => $value) {
      $ve=(array)DB::table('vehicles')
      ->leftJoin('delivery_order_drivers as dod','dod.id','vehicles.id')
      ->leftJoin('contacts','contacts.id','dod.driver_id')
      ->leftJoin('manifests','manifests.id','dod.manifest_id')
      ->leftJoin('routes','routes.id','manifests.route_id')
      ->where('vehicles.gps_no', $value->msisdn)
      ->selectRaw('
      contacts.name as driver,
      dod.code as code_sj,
      routes.name as trayek,
      dod.id as id,
      dod.job_status_id
      ')->first();
      if (!$ve) {
        continue;
      }
      $data[]=[
        'no_pol' => $value->no_pol,
        'latitude' => $value->latitude,
        'longitude' => $value->longitude,
        'gps_time' => $value->gps_time,
        'address' => $value->address,
        'acc' => $value->acc,
        'trayek' => $ve['trayek'],
        'driver' => $ve['driver'],
        'code_sj' => $ve['code_sj'],
        'delivery_id' => $ve['id'],
        'job_status_id' => $ve['job_status_id'],
      ];
    }
    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }
  public function get_last_position_by_vendor_2(Request $request)
  {
    $params=[
      'memberCode' => 'BCS',
      'password' => 'Z170KPJpT4pPcgmX',
      'lastPositionId' => 0,
      'maxCount' => 100,
    ];
    $url="http://api.inovatrack.com/api/data/GetPositions".'?'.http_build_query($params);

    $get=Curl::to($url)
        ->asJson()
        ->get();
        // dd($get);
    return Response::json($get,200,[],JSON_NUMERIC_CHECK);
  }

  public static function voyage_schedule_query(Request $request)
  {
    $wr = '1=1';

    if($request->ships)
        $wr .= " AND voyage_schedules.vessel_id = {$request->ships}";

    if($request->start_date_eta)
        $wr .= " AND voyage_schedules.eta >= '". dateDB($request->start_date_eta). ' 00:00:00' ."'";
    if($request->end_date_eta)
        $wr .= " AND voyage_schedules.eta <= '". dateDB($request->end_date_eta). ' 23:59:59' ."'";

    if($request->start_date_etd)
        $wr .= " AND voyage_schedules.etd >= '". dateDB($request->start_date_etd). ' 00:00:00' ."'";
    if($request->end_date_etd)
        $wr .= " AND voyage_schedules.etd <= '". dateDB($request->end_date_etd). ' 23:59:59' ."'";

    return VoyageSchedule::with('vessel','pol','pod')
        ->leftJoin(DB::raw("(select voyage_schedule_id, count(*) as total from containers group by voyage_schedule_id) Y"),'Y.voyage_schedule_id','voyage_schedules.id')
        ->whereRaw($wr)
        ->select('voyage_schedules.*','Y.total');
  }

  public static function container_query(Request $request)
  {
    $wr="1=1";
    if (isset($request->is_fcl)) {
      $wr.=" AND is_fcl = $request->is_fcl";
    }

    if (auth()->user()->is_admin==0) {
      $wr.=" AND containers.company_id = ".auth()->user()->company_id;
    }
    else if($request->company_id) {
      $wr.=" AND containers.company_id = " . $request->company_id;
    }

    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';

    if ($start_date != '' && $end_date != '') {
      $wr.=" AND booking_date BETWEEN '$start_date' AND '$end_date'";
    }

    $dt = Container::with('company:id,name','voyage_schedule','voyage_schedule.vessel','container_type')->whereRaw($wr);

    if($request->job_order_id) {
        $job_order_id = $request->job_order_id;
        $dt = $dt->whereRaw("containers.id IN (SELECT container_id FROM job_order_containers WHERE job_order_id = $job_order_id) ");
    }

    return $dt;
  }

  public function jo_cost_vendor_datatable(Request $request)
  {
    $data = DB::table('job_order_costs as joc');
    $data = $data->leftJoin('job_orders as jo','jo.id','joc.header_id');
    $data = $data->leftJoin('cost_types as ct','ct.id','joc.cost_type_id');
    $data = $data->where('joc.manifest_cost_id', null);
    $data = $data->where('joc.type', 1);
    $data = $data->where('ct.type', 1);
    $data = $data->where(function($query){
        $query->where('joc.status', 5);
        $query->orWhere('joc.status', 8);
    });
    $data = $data->where('joc.is_invoice', 0);
    $data = $data->select([
      'joc.id',
      'jo.code',
      DB::raw('concat(ct.code,\' - \',ct.name) as name'),
      'joc.total_price',
      'joc.description',
      'jo.shipment_date'
    ]);
    if ($request->filled('vendor_id')) {
      $data = $data->where('joc.vendor_id', $request->vendor_id);
    }
    if ($request->filled('not_id')) {
      $data = $data->whereNotIn('joc.id', $request->not_id);
    }

    return DataTables::of($data)
    ->filterColumn('name', function($query, $keyword) {
      $sql="CONCAT(ct.code,'-',ct.name) like ?";
      $query->whereRaw($sql, ["%{$keyword}%"]);
    })
    ->toJson(true);
  }

  /*
      Date : 09-07-2021
      Description : Menampilkan daftar kategori klaim datatable
      Developer : Hendra
      Status : Create
    */
  public function claim_categories_datatable(Request $request)
  {
    $data = DB::table('claim_categories')
                    ->select('id', 'name');

    return DataTables::of($data)
            ->make(true);
  }

    /*
      Date : 09-07-2021
      Description : Menampilkan daftar klaim datatable
      Developer : Hendra
      Status : Create
    */
    public function claims_datatable(Request $request)
    {
        $dt = DB::table('claims')
        ->join('contacts AS customers', 'customers.id',  'claims.customer_id')
        ->leftJoin('contacts AS collects', 'collects.id',  'claims.collectible_id')
        ->leftJoin('job_orders', 'job_orders.id', 'claims.job_order_id')
        ->leftJoin('sales_orders', 'sales_orders.id', 'claims.sales_order_id')
        ->leftJoin('job_orders as jo_so', 'sales_orders.job_order_id', 'jo_so.id')
        ->join('companies', 'companies.id', 'claims.company_id')
        ->select('claims.id', 'claims.code', 'claims.date_transaction',
            'customers.name AS customer_name', 'collects.name AS collectible_name',
            'claim_type',
            'claims.status',
            DB::raw('IF(claims.status = 1, "Draft", "Disetujui") AS status_name'),
            'job_orders.code AS job_order_code', 'job_orders.shipment_date AS job_order_date', 
            'sales_orders.code AS sales_order_code', 'jo_so.shipment_date AS sales_order_date', 
            'companies.name AS company_name');
        if($request->filled('start_date')) {
            $dt = $dt->where('claims.date_transaction', '>=', dateDB($request->start_date));
        }
        if($request->filled('end_date')) {
            $dt = $dt->where('claims.date_transaction', '<=', dateDB($request->end_date));
        }

        if($request->filled('company_id')) {
            $dt->where('claims.company_id', $request->company_id);
        }

        if($request->filled('customer_id')) {
            $dt->where('claims.customer_id', $request->customer_id);
        }

        if($request->filled('status')) {
            $dt->where('claims.status', $request->status);
        }

        return DataTables::of($dt)
        ->addColumn('jo_so_code', function($row){
          return $row->job_order_code ?? $row->sales_order_code;
        })
        ->addColumn('jo_so_date', function($row){
          return $row->job_order_date ?? $row->sales_order_date;
        })
        ->make(true);
    }
}
