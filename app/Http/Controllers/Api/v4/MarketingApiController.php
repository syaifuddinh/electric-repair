<?php
namespace App\Http\Controllers\Api\v4;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\PriceList;
use App\Model\Quotation;
use App\Model\QuotationCost;
use App\Model\QuotationDetail;
use App\Model\QuotationHistoryOffer;
use App\Model\Lead;
use App\Model\LeadStatus;
use App\Model\LeadActivity;
use App\Model\WorkOrder;
use App\Model\WorkOrderDraft;
use App\Model\WorkOrderDetail;
use App\Model\Service;
use App\Model\Inquery;
use App\Model\InqueryCustomer;
use App\User;
use Carbon\Carbon;
use DataTables;
use Response;
use DB;

class MarketingApiController extends Controller
{
  public function price_list_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->disable4=='true') {
      $wr.=" AND price_lists.service_type_id != 4";
    }
    if (auth()->user()->is_admin==0) {
      $wr.=" AND price_lists.company_id = ".auth()->user()->company_id;
    }
    if (isset($request->service_id)) {
      $wr.=" AND price_lists.service_id = $request->service_id";
    }
    if (isset($request->service_type_id)) {
      $wr.=" AND price_lists.service_type_id = $request->service_type_id";
    }

    if (isset($request->company_id)) {
      $wr.=" AND price_lists.company_id = $request->company_id";
    }

    $user = $request->user();
    $item = PriceList::with('company','commodity','service','piece','route','moda','vehicle_type','container_type','service_type')->whereRaw($wr)->select('price_lists.*')->orderBy('created_at', 'DESC');

    return DataTables::of($item)
      ->addColumn('action', function($item) use ($user){
        $html = '';

        if($user->hasRole('marketing.price.price_list.detail'))
            $html .= "<a ui-sref=\"marketing.price_list.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

        if($user->hasRole('marketing.price.price_list.edit'))
            $html.="<a ui-sref=\"marketing.price_list.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";

        if($user->hasRole('marketing.price.price_list.delete'))
            $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";

        return $html;
      })
      ->addColumn('action_choose', function($item){
        // $html="<a ui-sref=\"marketing.price_list.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html="<button class='btn btn-xs btn-success' ng-click=\"choose([$item->id,'$item->name',$item->price_tonase,$item->price_volume,$item->price_item,$item->price_full])\">Pilih</button>";
        return $html;
      })
      ->addColumn('action_choose2', function($item){
        $html='<a ng-click=\'choosePriceList('.json_encode($item,JSON_HEX_APOS).')\' class="btn btn-xs btn-success">Pilih</a>';
        return $html;
      })
      ->rawColumns(['action','action_choose','action_choose2'])
      ->make(true);
  }

  public function inquery_datatable(Request $request)
  {
    $wr="1=1";
    if (isset($request->is_contact)) {
      $wr.=" and quotations.is_contract = $request->is_contact";
    }
    if (isset($request->status_approve)) {
      $wr.=" and quotations.status_approve = $request->status_approve";
    }
    if (isset($request->customer_id)) {
      $wr.=" and quotations.customer_id = $request->customer_id";
    } else {
      if (auth()->user()->is_admin==0) {
        $wr.=" and quotations.company_id = ".auth()->user()->company_id;
      }
    }
    if (isset($request->is_active)) {
      $wr.=" and quotations.is_active = $request->is_active";
    }
    if (isset($request->is_parent_null)) {
      $wr.=" and quotations.parent_id is null";
    }
    if (isset($request->end_date_more)) {
      $wr.=" and quotations.date_end_contract >= date(now())";
    }
    $item = Quotation::with('customer','sales','customer_stage')->whereRaw($wr);
    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status_approve', $status) : $item;
    $customer_stage_id = $request->customer_stage_id;
    $customer_stage_id = $customer_stage_id != null ? $customer_stage_id : '';
    $item = $customer_stage_id != '' ? $item->where('customer_stage_id', $customer_stage_id) : $item;
    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $item = $start_date != '' && $end_date != ' ' ? $item->whereBetween('date_inquery', [$start_date, $end_date]) : $item;

    $item = $item->select('quotations.*',DB::raw("IF(type_entry=1,'WEBSITE',IF(type_entry=2,'OPERATOR','ANDROID')) as type_entryy"));

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('marketing.quotation.detail')\" ui-sref=\"marketing.inquery.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->status_approve==1) {
          $html.="<a ng-show=\"roleList.includes('marketing.quotation.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->addColumn('action_customer', function($item){
        $html="<a ui-sref=\"main.quotation.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html='<a ng-click=\'chooseKontrak('.json_encode($item,JSON_HEX_APOS).')\' class="btn btn-xs btn-success">Pilih</a>';
        return $html;
      })
      ->editColumn('date_end_contract', function($item){
        return dateView($item->date_end_contract);
      })
      ->editColumn('status_approve', function($item){
        $stt=[
          1 => 'Penawaran',
          2 => 'Penawaran Diajukan',
          3 => 'Penawaran Disetujui',
          4 => 'Kontrak',
          5 => 'Penawaran Ditolak',
          6 => 'Batal Quotation',
        ];
        return $stt[$item->status_approve];
      })
      ->editColumn('bill_type', function($item){
        $stt=[
          1 => 'Per Pengiriman',
          2 => 'Borongan',
        ];
        return $stt[$item->bill_type];
      })
      ->filterColumn('type_entryy', function($query, $keyword) {
          $sql = "IF(type_entry=1,'WEBSITE',IF(type_entry=2,'OPERATOR','ANDROID')) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
          })
      ->rawColumns(['action','action_choose','action_customer'])
      ->make(true);
  }
  public function inquery_customer_datatable(Request $request)
  {
    $wr="1=1";
    $item = InqueryCustomer::with('customer')->whereRaw($wr);
    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('is_done', $status) : $item;
    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $item = $start_date != '' && $end_date != ' ' ? $item->whereBetween('created_at', [$start_date, $end_date]) : $item;
    $item = $item->select('inquery_customers.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ui-sref=\"marketing.inquery_customer.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->addColumn('action_customer', function($item){
        $html="<a ui-sref=\"main.inquery.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->editColumn('created_at', function($item){
        return dateView($item->created_at);
      })
      ->editColumn('is_done', function($item){
        $stt=[
          0 => 'Diajukan',
          1 => 'Selesai',
        ];
        return $stt[$item->is_done];
      })
      ->rawColumns(['action','action_customer'])
      ->make(true);
  }

  public function quotation_detail_datatable(Request $request)
  {
    $wr="1=1";
    if (isset($request->is_contact)) {
      $wr.=" and quotations.is_contract = $request->is_contact";
    }
    if (isset($request->customer_id)) {
      $wr.=" and quotations.customer_id = $request->customer_id";
    }
    if (isset($request->end_date_more)) {
      $wr.=" and quotations.date_end_contract >= date(now())";
    }
    if ($request->quotation_id) {
      $wr.=" and quotations.id = $request->quotation_id";
    } else {
      if (auth()->user()->is_admin==0) {
        $wr.=" AND quotations.company_id = ".auth()->user()->company_id;
      }
    }
    if ($request->no_service_4) {
      $wr.=" and quotation_details.service_type_id != 4";
    }
    $item = QuotationDetail::leftJoin('quotations','quotations.id','=','quotation_details.header_id')
    ->leftJoin('routes','routes.id','=','quotation_details.route_id')
    ->leftJoin('services','services.id','=','quotation_details.service_id')
    ->leftJoin('commodities','commodities.id','=','quotation_details.commodity_id')
    ->leftJoin('vehicle_types','vehicle_types.id','=','quotation_details.vehicle_type_id')
    ->leftJoin('container_types','container_types.id','=','quotation_details.container_type_id')
    ->leftJoin('service_types','service_types.id','=','services.service_type_id')
    ->whereRaw($wr)
    ->select([
      'quotation_details.id',
      'quotation_details.piece_name',
      'quotation_details.imposition',
      'quotation_details.price_contract_full',
      'routes.name as route_name',
      'vehicle_types.name as vehicle_type_name',
      DB::raw("CONCAT(container_types.size,' ',container_types.name) as container_type_name"),
      'commodities.name as commodity_name',
      'quotations.no_contract as code',
      'services.name as service',
      'service_types.name as service_type',
    ]);

    return DataTables::of($item)
      ->addColumn('action_choose', function($item){
        $html="<a ng-click='chooseKontrak($item->id,\"$item->code\")' class='btn btn-xs btn-success'>Pilih</a>";
        return $html;
      })
      ->editColumn('price_contract_full', function($item){
        return number_format($item->price_contract_full);
      })
      ->rawColumns(['action_choose'])
      ->make(true);
  }

  public function contract_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->customer_id) {
      $wr.=" AND quotations.customer_id = $request->customer_id";
    } else {
      if (auth()->user()->is_admin==0) {
        $wr.=" AND quotations.company_id = ".auth()->user()->company_id;
      }
    }
    $item = Quotation::with('customer','sales','customer_stage')->where('status_approve', 4)->where('is_hide', 0)->whereRaw($wr);

    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('is_active', $status) : $item;
    $customer_stage_id = $request->customer_stage_id;
    $customer_stage_id = $customer_stage_id != null ? $customer_stage_id : '';
    $item = $customer_stage_id != '' ? $item->where('customer_stage_id', $customer_stage_id) : $item;
    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $item = $start_date != '' && $end_date != ' ' ? $item->whereBetween('date_start_contract', [$start_date, $end_date]) : $item;

    $item = $item->select('quotations.*');
    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ui-sref=\"marketing.contract.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->addColumn('action_customer', function($item){
        $html="<a ui-sref=\"main.contract.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->rawColumns(['action','action_customer'])
      ->make(true);
  }

  public function inquery_detail_cost_datatable(Request $request)
  {
    $item = QuotationCost::with('vendor','cost_type')->where('quotation_detail_id', $request->quotation_detail_id)->select('quotation_costs.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        // $html="<a><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html="<a ng-show=\"roleList.includes('marketing.quotation.detail.detail_info.detail_cost.edit')\" ng-click=\"delete_cost($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->editColumn('cost', function($item){
        return formatPrice($item->cost);
      })
      ->editColumn('total_cost', function($item){
        return formatPrice($item->total_cost);
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function lead_amount()
  {
    $lead = Lead::where('step', 1)->count();
    $looseLead = Lead::where('step', 6)->count();

    $response['data'] = [
      'lead' => $lead,
      'loose' => $looseLead,
    ];

    return Response::json($response, 200,[],JSON_NUMERIC_CHECK);
  }
  public function lead_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->company_id) {
      $wr.=" AND leads.company_id = $request->company_id";
    }
    if ($request->step) {
      $wr.=" AND leads.step = $request->step";
    }
    if ($request->lead_status_id) {
      $wr.=" AND leads.lead_status_id = $request->lead_status_id";
    }
    if ($request->lead_source_id) {
      $wr.=" AND leads.lead_source_id = $request->lead_source_id";
    }
    if ($request->name) {
      $wr.=" AND leads.name LIKE '%$request->name%'";
    }
    $item = Lead::with('company','lead_source','lead_status')->whereRaw($wr)->select('leads.*',DB::raw("CONCAT(IFNULL(leads.phone,'-'),', ',IFNULL(leads.phone2,'-')) as phone_lengkap"));

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('marketing.leads.detail')\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data' ui-sref='marketing.lead.show({id:$item->id})'></span></a>&nbsp;&nbsp;";
        if ($item->step==1) {
          $html.="<a ng-show=\"roleList.includes('marketing.leads.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        }
        return $html;
      })
      ->filterColumn('phone_lengkap', function($query, $keyword) {
        $sql = "CONCAT(IFNULL(leads.phone,'-'),', ',IFNULL(leads.phone2,'-')) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->editColumn('step', function($item){
        $stt=[
          1 => 'Lead',
          2 => 'Opportunity',
          3 => 'Inquery',
          4 => 'Quotation',
          5 => 'Kontrak',
          6 => 'Batal Lead',
          7 => 'Batal Opportunity',
          8 => 'Batal Inquery',
          9 => 'Batal Quotation',
        ];
        return $stt[$item->step];
      })
      ->editColumn('created_at', function($item){
        return dateView($item->created_at);
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function lead_activity_datatable(Request $request)
  {
    $item = LeadActivity::leftJoin('leads','leads.id','lead_activities.header_id')
    ->where('header_id', $request->id)->selectRaw('lead_activities.*,leads.step');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="";
        if ($item->step <= 1) {
          $html.="<a ng-show=\"roleList.includes('marketing.leads.detail.activity.done')\" ng-click=\"done($item->id)\"><span class='fa fa-check'></span></a>&nbsp;|&nbsp;";
          $html.="<a ng-show=\"roleList.includes('marketing.leads.detail.activity.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        }
        return $html;
      })
      ->editColumn('date_activity', function($item){
        return dateView($item->date_activity);
      })
      ->editColumn('is_done', function($item){
        $stt=[
          1 => 'Sudah Selesai',
          0 => 'Belum Selesai',
        ];
        return $stt[$item->is_done];
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function contract_price_datatable(Request $request)
  {
    $wr="1=1 and quotations.is_contract = 1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND quotations.company_id = ".auth()->user()->company_id;
    } else if ($request->company_id) {
      $wr.=" AND quotations.company_id = $request->company_id";
    }

    if ($request->customer_id) {
      $wr.=" AND quotations.customer_id = $request->customer_id";
    }

    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and quotations.date_contract between '$start' and '$end'";
    }

    $item=DB::table('quotation_details')
    ->leftJoin('services','services.id','quotation_details.service_id')
    ->leftJoin('routes','routes.id','quotation_details.route_id')
    ->leftJoin('commodities','commodities.id','quotation_details.commodity_id')
    ->leftJoin('vehicle_types','vehicle_types.id','quotation_details.vehicle_type_id')
    ->leftJoin('container_types','container_types.id','quotation_details.container_type_id')
    ->leftJoin('quotations','quotations.id','quotation_details.header_id')
    ->leftJoin('companies','companies.id','quotations.company_id')
    ->leftJoin('contacts','contacts.id','quotations.customer_id')
    ->leftJoin('pieces','pieces.id','quotation_details.piece_id')
    ->leftJoin('impositions','impositions.id','quotation_details.imposition')
    ->whereRaw($wr)
    ->selectRaw("
    quotation_details.*,
    routes.name as trayek,
    services.name as service,
    commodities.name as commodity,
    if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as vehicle_type,
    quotations.no_contract,
    contacts.name as customer,
    companies.name as company,
    if(quotation_details.service_type_id in (6,7),pieces.name,if(quotation_details.service_type_id=2,'Kontainer',if(quotation_details.service_type_id=3,'Unit',impositions.name))) as imposition_name
    ");

    return DataTables::of($item)
      ->addColumn('action', function($item){
        // $html="<a><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html="<a ng-show=\"roleList.includes('marketing.price.contract_price.detail')\" ui-sref=\"marketing.contract_price.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->filterColumn('imposition_name', function($query, $keyword) {
        $sql="if(quotation_details.service_type_id in (6,7),pieces.name,if(quotation_details.service_type_id=2,'Kontainer',if(quotation_details.service_type_id=3,'Unit',impositions.name))) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->filterColumn('vehicle_type', function($query, $keyword) {
        $sql="if(vehicle_types.id is not null,vehicle_types.name,container_types.code) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->editColumn('price_contract_full', function($item){
        return number_format($item->price_contract_full);
      })
      ->editColumn('imposition', function($item){
        $ret="";
        $stt=[
          1 => 'Kubikasi',
          2 => 'Tonase',
          3 => 'Item',
        ];
        if (isset($item->imposition)) {
          $ret.=$stt[$item->imposition];
        }
        return $ret;
      })
      ->editColumn('is_generate', function($item){
        $stt=[
          1 => 'Aktif',
          0 => 'Tidak Aktif',
        ];
        return $stt[$item->is_generate];
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function opportunity_datatable(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND inqueries.company_id = ".auth()->user()->company_id;
    }

    $item = Inquery::with('customer','customer_stage','sales_opportunity')->whereRaw($wr)->whereIn('status', [1,5]);

    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status', $status) : $item;
    $customer_stage_id = $request->customer_stage_id;
    $customer_stage_id = $customer_stage_id != null ? $customer_stage_id : '';
    $item = $customer_stage_id != '' ? $item->where('customer_stage_id', intval($customer_stage_id)) : $item;
    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $item = $start_date != '' && $end_date != ' ' ? $item->whereBetween('date_opportunity', [$start_date, $end_date]) : $item;

    $item = $item->select('inqueries.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('marketing.opportunity.detail')\" ui-sref=\"marketing.opportunity.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if (in_array($item->status,[1])) {
          $html.="<a ng-show=\"roleList.includes('marketing.opportunity.edit')\" ui-sref=\"marketing.opportunity.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('marketing.opportunity.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        }
        return $html;
      })
      ->editColumn('date_opportunity', function($item){
        return dateView($item->date_opportunity);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => 'Opportunity',
          2 => 'Inquery',
          3 => 'Quotation',
          4 => 'Contract',
          5 => 'Batal Opportunity',
          6 => 'Batal Inquery',
          7 => 'Batal Quotation',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function inquery_qt_datatable(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND inqueries.company_id = ".auth()->user()->company_id;
    }
    $item = Inquery::with('customer','customer_stage','sales_inquery')->whereRaw($wr)->whereIn('status', [2,6]);


    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status', $status) : $item;
    $customer_stage_id = $request->customer_stage_id;
    $customer_stage_id = $customer_stage_id != null ? $customer_stage_id : '';
    $item = $customer_stage_id != '' ? $item->where('customer_stage_id', $customer_stage_id) : $item;
    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $item = $start_date != '' && $end_date != ' ' ? $item->whereBetween('date_inquery', [$start_date, $end_date]) : $item;

    $user = $request->user();
    $item = $item->select('inqueries.*');

    return DataTables::of($item)
      ->addColumn('action', function($item) use ($user) {
        $html = '';

        if($user->hasRole('marketing.Inquery.detail'))
          $html .= "<a ui-sref=\"marketing.inquery_qt.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if (in_array($item->status,[1,2])) {
          if($user->hasRole('marketing.Inquery.edit'))
            $html.="<a ui-sref=\"marketing.inquery_qt.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        
          if($user->hasRole('marketing.Inquery.delete'))
            $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        }
        return $html;
      })
      ->editColumn('date_inquery', function($item){
        return dateView($item->date_inquery);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => 'Opportunity',
          2 => 'Inquery',
          3 => 'Quotation',
          4 => 'Contract',
          5 => 'Batal Opportunity',
          6 => 'Batal Inquery',
          7 => 'Batal Quotation',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function inquery_offer_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->quotation_detail_id) {
      $wr.=" AND quotation_history_offers.quotation_detail_id = $request->quotation_detail_id";
    }
    // $item = QuotationHistoryOffer::whereRaw($wr)->orderBy('created_at', 'desc')->select('quotation_history_offers.*');
    $item=DB::table('quotation_history_offers')
    ->leftJoin('quotation_details','quotation_details.id','quotation_history_offers.quotation_detail_id')
    ->leftJoin(DB::raw('(select sum(total_cost) as total_cost,quotation_detail_id from quotation_costs group by quotation_detail_id) as qc'),'qc.quotation_detail_id','quotation_history_offers.quotation_detail_id')
    ->whereRaw($wr)
    ->selectRaw('
    quotation_history_offers.id,
    quotation_history_offers.price,
    quotation_history_offers.total_offering,
    quotation_history_offers.status,
    quotation_history_offers.created_at,
    qc.total_cost
    ');

    return DataTables::of($item)
      ->editColumn('created_at', function($item){
        return dateFullTime($item->created_at);
      })
      ->editColumn('total_offering', function($item){
        return formatNumber($item->total_offering);
      })
      ->editColumn('price', function($item){
        return formatNumber($item->price);
      })
      ->editColumn('total_cost', function($item){
        return formatNumber($item->total_cost);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => '<span class="badge badge-warning">Diajukan</span>',
          2 => '<span class="badge badge-success">Disetujui</span>',
          3 => '<span class="badge badge-danger">Ditolak</span>',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['status'])
      ->make(true);
  }
  public function work_order_amount($filter, Request $request)
  {
    // dd($request);
    $response['data'] = [];
    $dateRequest = new Carbon(date('Y-m-d', strtotime($request->date)));
    $year = $dateRequest->year;

    if ($filter=='date') {
      $daysInMonth = $dateRequest->daysInMonth;
      $startMonth = $dateRequest->startOfMonth();

      $date = $startMonth;
      // return $daysInMonth;
      while ((int)$date->format('d') <= $daysInMonth) {
        $filterDate = $date->format('Y-m-d');

        $response['data'][] = [
          'date' => $date->format('d-m-Y'),
          'data' => DB::table('work_orders')->leftJoin('job_orders','job_orders.job_order_id','work_orders.id')->selectRaw('
            work_orders.status, count(*) as totalAmount,
            sum(job_orders.total_price) as summary
            ')
          ->groupBy('work_orders.id')->get()
        ];

        if ((int)$date->format('d') == $daysInMonth) {
          break;
        }
        $date->addDay();
      }
    } else {
      $totalMonth=12;
      $ptrMonth=1;

      while ($ptrMonth <= $totalMonth) {
        $startMonth = $dateRequest->copy()->startOfMonth()->format("$year-$ptrMonth-d");
        $endMonth = $dateRequest->copy()->endOfMonth()->format("$year-$ptrMonth-d");

        $year=Carbon::now()->format('Y');
        $wr="1=1";
        $item = DB::table('work_orders as wo')
        ->leftJoin('work_order_details as wod', 'wo.id', 'wod.header_id')
        ->leftJoin('quotations as qt', 'wo.quotation_id', 'qt.id')
        ->leftJoin('quotation_details as qd', 'wod.quotation_detail_id', 'qd.id')
        ->leftJoin('services as s', 's.id', 'qd.service_id')
        ->leftJoin('service_groups as sg', 'sg.id', 's.service_group_id')
        ->leftJoin('job_orders as jo','jo.work_order_detail_id','wod.id')
        ->leftJoin('job_order_costs as joc','joc.header_id','jo.id')
        ->whereRaw($wr)
        ->selectRaw("
          if(qt.bill_type=2,(wo.qty*qt.price_full_contract),sum(distinct jo.total_price)) as summary,
          wo.status,
          count(distinct wod.header_id) as totalAmount,
          wo.date
        ")
        ->groupBy('wo.id')
        ->havingRaw("year(wo.date) = '$year' and month(wo.date) = '$ptrMonth'")
        ->get();

        $response['data'][] = [
          'month' => $ptrMonth,
          'data' => DB::table('work_orders')
          ->leftJoin('job_orders','job_orders.work_order_id','work_orders.id')
          ->selectRaw("
          work_orders.status,
          count(distinct work_orders.id) as totalAmount,
          sum(job_orders.total_price) as summary")
          ->whereBetween('work_orders.date', [$startMonth, $endMonth])
          ->groupBy('work_orders.id')
          ->get()
        ];
        // $wod=DB::table('work_orders')
        // ->leftJoin('job_orders','job_orders.work_order_id','work_orders.id')
        // ->whereBetween('work_orders.created_at', [$startMonth, $endMonth])
        // ->selectRaw('work_orders.status,count(*) as totalAmount, sum(job_orders.total_price) as summary')
        // ->groupBy('work_orders.status')
        // ->get();
        // dd($wod);
        if ($ptrMonth == $totalMonth) {
          break;
        }
        $ptrMonth++;
      }
    }
    // dd($response);
    return Response::json($response, 200, [], JSON_NUMERIC_CHECK);
  }
  public function work_order_trend(Request $request)
  {
    $response['data'] = [];
    $date = new Carbon(date('Y-m-d', strtotime($request->date)));
    $startPeriode = $date->copy()->startOfMonth()->format('Y-m-d');
    $endPeriode = $date->copy()->endOfMonth()->format('Y-m-d');

    $months = $date->daysInMonth;
    $j = 1;

    $trendInMonth = WorkOrder::selectRaw("service_id, count(service_id)")
    ->leftJoin('work_order_details', 'work_order_details.header_id', 'work_orders.id')
    ->leftJoin('quotation_details', 'quotation_details.id', 'work_order_details.quotation_detail_id')
    ->leftJoin('services', 'quotation_details.service_id', 'services.id')
    ->whereBetween('work_orders.date', [$startPeriode, $endPeriode])
    ->groupBy('service_id')
    ->orderBy('total', 'DESC')
    ->limit(5);

    $trendInMonth = $trendInMonth->pluck('service_id')->toArray();
    for ($i = 1; $i <= $months; $i++) {
        if ($date->englishDayOfWeek == 'Sunday') {
            $dateFrom = date('Y-m-'.$j, strtotime($request->date));
            $dateTo = date('Y-m-'.$i, strtotime($request->date));

            foreach ($trendInMonth as $value) {
              if(!empty($value))
              {
                $service = Service::find($value);

                $trend = WorkOrder::selectRaw("service_id, count(service_id) as total")
                ->leftJoin('work_order_details', 'work_order_details.header_id', 'work_orders.id')
                ->leftJoin('quotation_details', 'quotation_details.id', 'work_order_details.quotation_detail_id')
                ->leftJoin('services', 'quotation_details.service_id', 'services.id')
                ->whereBetween('work_orders.date', [$dateFrom, $dateTo])
                ->where('service_id', $value)
                ->groupBy('service_id')
                ->first();

                $services[$service->id]['service'] = $service->name;
                $services[$service->id]['datas'][] = !empty($trend->total) ? $trend->total:0;
              }
            }

            $j = $i+1;
        }

        if ($i == $months && $date->englishDayOfWeek != 'Sunday') {
            $dateFrom = date('Y-m-'.$j, strtotime($request->date));
            $dateTo = date('Y-m-'.$i, strtotime($request->date));

            foreach ($trendInMonth as $value) {
              if(!empty($value))
              {
                $service = Service::find($value);

                $trend = WorkOrder::selectRaw("service_id, services.name, count(service_id) as total, work_orders.date")
                ->leftJoin('work_order_details', 'work_order_details.header_id', 'work_orders.id')
                ->leftJoin('quotation_details', 'quotation_details.id', 'work_order_details.quotation_detail_id')
                ->leftJoin('services', 'quotation_details.service_id', 'services.id')
                ->whereBetween('work_orders.date', [$dateFrom, $dateTo])
                ->where('service_id', $value)
                ->groupBy('service_id')
                ->first();

                $services[$service->id]['service'] = $service->name;
                $services[$service->id]['datas'][] = !empty($trend->total) ? $trend->total:0;
              }
            }

            $j = $i+1;
        }

        $date->addDay(1);
    }

    $response['data'] = $services;
    return Response::json($response, 200, [], JSON_NUMERIC_CHECK);
  }

  public function work_order_trend_new(Request $request)
  {
    // dd($data);
    $data=[];
    $serviceGroup=DB::table('service_groups')->selectRaw('id,name')->get();
    foreach ($serviceGroup as $key => $value) {
      $month=[1,2,3,4,5,6,7,8,9,10,11,12];
      $year=Carbon::now()->parse('Y');
      $data[$key]=[
        'name' => $value->name
      ];
      foreach ($month as $mnt) {
        $dt=DB::table('job_orders')
        ->leftJoin('services','services.id','job_orders.service_id')
        ->whereRaw("month(shipment_date) = '$mnt' and year(shipment_date) = '$year' and services.service_group_id = $value->id")
        ->selectRaw('ifnull(count(distinct job_orders.work_order_id),0) as total')
        ->first();
        $data[$key]['data'][]=$dt->total;
      }
    }
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
   public function work_order_datatable(Request $request)
  {
    $wr="1=1";
    // if (isset($request->service_not_in)) {
    //   foreach ($request->service_not_in as $key => $value) {
    //     $wr.=" AND service_type_id != ".$value;
    //   }
    // }

    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $wr .= $start_date != '' && $end_date != ' ' ? " AND work_orders.date BETWEEN '$start_date' AND '$end_date'" : '';
    if (isset($request->customer_id)) {
      $wr.=" AND work_orders.customer_id = $request->customer_id";
    }
    if ($request->is_not_invoice) {
      $wr.=" AND (quotations.bill_type = 1 AND jos.total_no_invoice > 0)";
    }

    if (isset($request->is_invoice)) {
      $wr.=" AND work_orders.is_invoice = 1";
    }
    if ($request->wo_done) {
      $wr.=" AND work_orders.status = 2";
    }
    if ($request->status) {
      $wr.=" AND work_orders.status = $request->status";
    }

    // if ($request->company_id) {
    //   $wr.=" AND work_orders.company_id = $request->company_id";
    // }
    if (auth()->user()->is_admin==0) {
      $wr.=" AND work_orders.company_id = ".auth()->user()->company_id;
    }
    else {
          if (isset($request->company_id)) {
            $wr.=" AND work_orders.company_id = $request->company_id";
          }

    }

    $item = WorkOrder::with('customer','company','quotation')
    ->leftJoin('quotations','quotations.id','=','work_orders.quotation_id')
    ->leftJoin(DB::raw("(select work_order_id, sum(IF(invoice_id is null,1,0)) as total_no_invoice from job_orders group by work_order_id) jos"),"jos.work_order_id","=","work_orders.id")
    ->leftJoin(DB::raw('(select jo.work_order_id,group_concat(distinct no_po_customer) as po_customer, CONCAT("<ul>", group_concat(distinct "<li>", invoices.code, "</li>" SEPARATOR "<br>"), "</ul>") as invoice_code from job_orders as jo LEFT JOIN invoice_details ON invoice_details.job_order_id = jo.id LEFT JOIN invoices ON invoices.id = invoice_details.header_id group by jo.work_order_id) jo'),'jo.work_order_id','work_orders.id')
    ->whereRaw($wr)
    ->selectRaw('work_orders.*, jos.total_no_invoice, jo.po_customer, jo.invoice_code');

    $user = $request->user();

    return DataTables::of($item)
      ->addColumn('action', function($item) use ($user){
        $html = '';

        if($user->hasRole('marketing.work_order.detail'))
            $html .= "<a ui-sref='marketing.work_order.show({id:$item->id})'><span class='fa fa-folder-o' data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        
        if ($item->status == 1 && $user->hasRole('marketing.work_order.edit'))
            $html .= "<a ui-sref='marketing.work_order.edit({id:$item->id})'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        
        if ($item->total_job_order < 1 && $user->hasRole('marketing.work_order.delete'))
            $html .= "<a ng-click='deletes($item->id)'><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        
        return $html;
      })
      ->addColumn('action_operasional', function($item) use ($user) {
        $html = '';

        if ($user->hasRole('operational.work_order.detail'))
            $html = "<a ui-sref='marketing.work_order.show({id:$item->id})'><span class='fa fa-folder-o' data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        
        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html="<a ng-click='selectWO($item->id,\"$item->code\")' class='btn btn-xs btn-success'>Pilih</a>";
        return $html;
      })
      ->editColumn('date', function($item){
        return dateView($item->date);
      })
      ->editColumn('status', function($item){
        $stt=[
          1=>'Proses',
          2=>'Selesai',
        ];
        return $stt[$item->status];
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
      ->rawColumns(['action','action_choose','aju_number','no_bl','action_operasional', 'invoice_code'])
      ->make(true);
  }
  public function work_order_draft_datatable(Request $request)
  {
    $wr="1=1";
    // if (isset($request->service_not_in)) {
    //   foreach ($request->service_not_in as $key => $value) {
    //     $wr.=" AND service_type_id != ".$value;
    //   }
    // }
    if ($request->customer_id) {
      $wr.=" AND work_order_drafts.customer_id = $request->customer_id";
    }

    if (isset($request->is_done) && !is_null($request->is_done)) {
      $wr.=" AND work_order_drafts.is_done = $request->is_done";
    }

    // if ($request->company_id) {
    //   $wr.=" AND work_orders.company_id = $request->company_id";
    // }
    if (auth()->user()->is_admin==0) {
      $wr.=" AND work_orders.company_id = ".auth()->user()->company_id;
    }

    $item = DB::table('work_order_drafts')
    ->leftJoin('contacts','contacts.id','=','work_order_drafts.customer_id')
    ->leftJoin('users','users.id','=','work_order_drafts.create_by')
    ->selectRaw("work_order_drafts.*,users.name as user,contacts.name as customer")
    ->whereRaw($wr);

    return DataTables::of($item)
      ->addColumn('action', function($item){
        // $html="<a ng-click='requestDone($item->id)'><i class='fa fa-check'></i></a>&nbsp;";
        $html="<a ng-click='goRequest($item->id)'><i class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></i></a>";
        return $html;
      })
      ->editColumn('date', function($item){
        return dateView($item->date);
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
      ->rawColumns(['action','aju_number','no_bl'])
      ->toJson();
  }

  public function draft_check()
  {
    $wr="is_done = 0";

    if (auth()->user()->is_admin==0)
      $wr.=" AND company_id = ".auth()->user()->company_id;

    $data=DB::table('work_order_drafts')->whereRaw($wr)->count();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function work_order_detail_datatable(Request $request)
  {
    $wr="1=1";
    if (isset($request->customer_id)) {
      $wr.=" AND work_orders.customer_id = $request->customer_id";
    }
    if (isset($request->is_done)) {
      $wr.=" AND work_order_details.is_done = $request->is_done";
    }
    if (isset($request->is_operasional)) {
      if($request->is_operasional == 1) {

        $wr.=" AND work_order_details.service_type_name != 'LAYANAN WAREHOUSE'";
      }
      else {
        $wr.=" AND work_order_details.service_type_name = 'LAYANAN WAREHOUSE'";

      }
    }
    if (isset($request->filter_qty)) {
      $wr.=" AND work_order_details.qty_leftover > 0";
    }
    // if ($request->company_id) {
    //   $wr.=" AND work_orders.company_id = $request->company_id";
    // }
    if (isset($request->service_type_id)) {
      $wr.=" AND IF(work_order_details.quotation_detail_id is not null,quotation_details.service_type_id=$request->service_type_id,price_lists.service_type_id=$request->service_type_id)";
    }

    if (isset($request->is_handling)) {
      if($request->is_handling == 1) {

        $wr.=" AND (service1.is_warehouse = 1 OR service2.is_warehouse = 1 OR service3.is_warehouse = 1) AND (service1.name LIKE '%handling%' OR service2.name LIKE '%handling%' OR service3.name LIKE '%handling%')";
      }
    }

    if (isset($request->is_stuffing)) {
      if($request->is_stuffing == 1) {

        $wr.=" AND (service1.is_warehouse = 1 OR service2.is_warehouse = 1 OR service3.is_warehouse = 1) AND (service1.name LIKE '%stuffing%' OR service2.name LIKE '%stuffing%' OR service3.name LIKE '%stuffing%')";
      }
    }
    if (auth()->user()->is_admin==0) {
      $wr.=" AND work_orders.company_id = ".auth()->user()->company_id;

    }

    
    $sql="
    select
    work_orders.company_id as company_id,
    work_orders.id as work_order_id,
    work_orders.code as code,
    work_order_details.id as work_order_detail_id,
    work_order_details.qty_leftover,
    if(work_order_details.quotation_detail_id is not null,1,if(work_orders.is_customer_price = 0, 2, 3)) as type_tarif,
    if(service1.name is not null,service1.id,if(work_orders.is_customer_price = 0, service2.id, service3.id)) as service_id,
    if(service1.name is not null,service1.name,if(service2.name is not null, service2.name, service3.name)) as service_name,
    if(work_order_details.quotation_detail_id is not null,'Kontrak',if(work_orders.is_customer_price = 1, 'Tarif Customer', 'Tarif Umum')) as type_tarif_name 
    from work_order_details
    left join work_orders on work_orders.id = work_order_details.header_id
    left join quotation_details on quotation_details.id = work_order_details.quotation_detail_id
    left join price_lists on price_lists.id = work_order_details.price_list_id
    left join customer_prices on customer_prices.id = work_order_details.customer_price_id
    left join pieces P on customer_prices.piece_id = P.id
    left join services as service1 on service1.id = quotation_details.service_id
    left join services as service2 on service2.id = price_lists.service_id
    left join services as service3 on service3.id = customer_prices.service_id
    left join routes as trayek1 on trayek1.id = quotation_details.route_id
    left join routes as trayek2 on trayek2.id = price_lists.route_id
    left join commodities as comm1 on comm1.id = quotation_details.commodity_id
    left join commodities as comm2 on comm2.id = price_lists.commodity_id
    left join (select if(service_type_id in (6,7),pieces.name,if(service_type_id=2,'Kontainer',if(service_type_id=3,'Unit',impositions.name))) as imposition_name, quotation_details.id from quotation_details left join pieces on pieces.id = quotation_details.piece_id left join impositions on impositions.id = quotation_details.imposition group by quotation_details.id) Y on Y.id = work_order_details.quotation_detail_id
    left join (select if(service_type_id in (6,7),pieces.name,if(service_type_id=2,'Kontainer',if(service_type_id=3,'Unit','Kubikasi/Tonase/Item'))) as imposition_name, price_lists.id from price_lists left join pieces on pieces.id = price_lists.piece_id group by price_lists.id) X on X.id = work_order_details.price_list_id
    where $wr";
    $item=DB::select($sql);
    return DataTables::of($item)
      ->toJson();
  }

  public function activity_work_order(Request $request)
  {
    $wr="1=1";
    if ($request->customer_id) {
      $wr.=" AND wo.customer_id = $request->customer_id";
    }
    if ($request->company_id) {
      $wr.=" AND wo.company_id = $request->company_id";
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and wo.date between '$start' and '$end'";
      // dd($request);
    }
    $item=DB::table('work_orders as wo')
    ->leftJoin('contacts','contacts.id','wo.customer_id')
    ->leftJoin(DB::raw("(select sum(distinct iv.grand_total) as grand_total,group_concat(distinct iv.code) as code,group_concat(distinct iv.date_invoice) as date_invoice, job_orders.work_order_id from invoices as iv left join invoice_details as ivd on ivd.header_id = iv.id left join job_orders on job_orders.id = ivd.job_order_id group by job_orders.work_order_id) as Y"),'Y.work_order_id','wo.id')
    ->leftJoin(DB::raw("(select sum(if(joc.type=1,joc.total_price,0)) as operasional,sum(if(joc.type=2,joc.total_price,0)) as reimburse, jo.work_order_id from job_order_costs as joc left join job_orders as jo on jo.id = joc.header_id where joc.status in (3,5,8) group by jo.work_order_id) as X"),'X.work_order_id','wo.id')
    ->whereRaw($wr)
    ->selectRaw("
      distinct
      wo.code as code_wo,
      wo.date as date_wo,
      ifnull(Y.grand_total,0) as invoice_price,
      ifnull(X.operasional,0) as operational_price,
      ifnull(X.reimburse,0) as talangan_price,
      Y.code as code_invoice,
      Y.date_invoice,
      contacts.name as customer,
      concat('') as description,
      if(Y.grand_total is not null,ifnull(Y.grand_total,0)-ifnull(X.operasional,0)-ifnull(X.reimburse,0),0) as profit,
      if(Y.grand_total is not null,round((ifnull(Y.grand_total,0)-ifnull(X.operasional,0)-ifnull(X.reimburse,0))/ifnull(Y.grand_total,0)*100,2),0) as presentase
    ");
    return DataTables::of($item)
      ->filterColumn('profit', function($query, $keyword) {
        $sql="if(Y.grand_total is not null,ifnull(Y.grand_total,0)-ifnull(X.operasional,0)-ifnull(X.reimburse,0),0) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->filterColumn('presentase', function($query, $keyword) {
        $sql="if(Y.grand_total is not null,round((ifnull(Y.grand_total,0)-ifnull(X.operasional,0)-ifnull(X.reimburse,0))/ifnull(Y.grand_total,0)*100,2),0) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->filterColumn('description', function($query, $keyword) {
        $sql="CONCAT('') like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->editColumn('date_wo', function($item){
        return dateView($item->date_wo);
      })
      ->editColumn('operational_price', function($item){
        return formatNumber($item->operational_price);
      })
      ->editColumn('talangan_price', function($item){
        return formatNumber($item->talangan_price);
      })
      ->editColumn('invoice_price', function($item){
        return formatNumber($item->invoice_price);
      })
      ->editColumn('profit', function($item){
        return formatNumber($item->profit);
      })
      ->editColumn('presentase', function($item){
        return formatNumber($item->presentase).' %';
      })
      // ->editColumn('status', function($item){
      //   $stt=[
      //     1 => '<span class="badge badge-warning">Diajukan</span>',
      //     2 => '<span class="badge badge-success">Disetujui</span>',
      //     3 => '<span class="badge badge-danger">Ditolak</span>',
      //   ];
      //   return $stt[$item->status];
      // })
      // ->rawColumns(['status'])
      ->make(true);
  }
  public function activity_job_order(Request $request)
  {
    $wr="1=1";
    if ($request->customer_id) {
      $wr.=" AND job_orders.customer_id = $request->customer_id";
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and date(job_orders.shipment_date) between '$start' and '$end'";
    }
    if ($request->service_id) {
      $wr.=" and job_orders.service_id = $request->service_id";
    }
    $sql="
    SELECT
    	job_orders.shipment_date as date_jo,
    	job_orders.code as code_jo,
    	contacts.name as customer,
    	services.name as service,
    	froms.name as city_from,
    	tos.name as city_to,
    	ifnull(X.qty,0) as qty,
    	job_orders.total_price as operational,
    	ifnull(Y.total,0) as biaya,
    	job_orders.description as description,
    	null as satuan
    FROM
    	job_orders
    	LEFT JOIN routes ON job_orders.route_id = routes.id
    	LEFT JOIN cities as froms ON routes.city_from = froms.id
    	LEFT JOIN cities as tos ON routes.city_to = tos.id
    	LEFT JOIN services ON job_orders.service_id = services.id
    	LEFT JOIN contacts ON job_orders.customer_id = contacts.id
    	LEFT JOIN (select header_id, sum(total_price) as total, sum(qty) as qty from job_order_details group by header_id) X on X.header_id = job_orders.id
    	LEFT JOIN (select header_id, sum(total_price) as total, sum(qty) as qty from job_order_costs where type = 1 group by header_id) Y on Y.header_id = job_orders.id
    WHERE $wr
    ORDER BY job_orders.created_at desc
    ";
    $item = DB::select($sql);

    return DataTables::of($item)
      ->editColumn('date_jo', function($item){
        return dateView($item->date_jo);
      })
      ->editColumn('operational', function($item){
        return formatNumber($item->operational);
      })
      ->editColumn('biaya', function($item){
        return formatNumber($item->biaya);
      })
      // ->editColumn('status', function($item){
      //   $stt=[
      //     1 => '<span class="badge badge-warning">Diajukan</span>',
      //     2 => '<span class="badge badge-success">Disetujui</span>',
      //     3 => '<span class="badge badge-danger">Ditolak</span>',
      //   ];
      //   return $stt[$item->status];
      // })
      // ->rawColumns(['status'])
      ->toJson();
  }

}
