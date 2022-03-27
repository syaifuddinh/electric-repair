<?php

namespace App\Http\Controllers\Api\v4\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Service;
use App\Model\Contact;
use App\Model\Piece;
use App\Model\CostType;
use App\Model\Moda;
use App\Model\VehicleType;
use App\Model\Commodity;
use App\Model\Quotation;
use App\Model\QuotationCost;
use App\Model\WorkOrder;
use App\Model\WorkOrderDetail;
use App\Model\ContainerType;
use App\Model\ContactAddress;
use App\Model\QuotationDetail;
use App\Model\Route as Trayek;
use App\Model\Port;
use App\Model\Vessel;
use App\Model\JobOrder;
use App\Model\JobOrderDetail;
use App\Model\JobOrderCost;
use App\Model\JobOrderReceiver;
use App\Model\JobOrderDocument;
use App\Model\PriceList;
use App\Model\KpiLog;
use App\Model\KpiStatus;
use App\Model\Manifest;
use App\Model\ManifestDetail;
use App\Model\ManifestCost;
use App\Model\RouteCost;
use App\Model\RouteCostDetail;
use App\Model\SubmissionCost;
use App\Model\VoyageSchedule;
use App\Model\Container;
use App\Model\Notification;
use App\Model\NotificationUser;
use App\Model\Item;
use App\Model\Journal;
use App\Model\JournalDetail;
use App\Model\Payable;
use App\Model\PayableDetail;
use App\Model\Handling;
use App\Model\Stuffing;
use App\Model\Rack;
use App\Model\StorageType;
use App\Model\WarehouseStockDetail;
use App\Utils\TransactionCode;
use Carbon\Carbon;
use DB;
use Response;
use File;
use Auth;
use QrCode;

class JobOrderController extends Controller
{
/**
* Display a listing of the resource.
*
* @return \Illuminate\Http\Response
*/
public function index()
{
    $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address','company_id')->get();
    $data['services']=Service::with('service_type', 'kpi_statuses')->get();
    $data['kpi_statuses']=DB::table('kpi_statuses')->whereRaw('1=1')->select('name')->groupBy('name')->get();

    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

public function get_warehouse_items(Request $request)
{
    $data['item']=Item::with('customer', 'sender', 'receiver', 'piece')
    ->leftJoin('warehouse_stocks', 'item_id', 'items.id')
    ->leftJoin('warehouses', 'warehouse_id', 'warehouses.id')
    ->whereRaw('customer_id IS NOT NULL AND (qty IS NOT NULL OR qty != 0)');

    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

/**
* Show the form for creating a new resource.
*
* @return \Illuminate\Http\Response
*/
public function create()
{
    $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address','company_id')->get();
    $data['staff_gudang']=Contact::whereRaw("is_staff_gudang = 1")->select('id','name','address','company_id')->get();
    $data['services']=Service::with('service_type')->get();
    $data['moda']=Moda::select('id','name')->get();
    $data['trayek']=Trayek::select('id','name')->get();
    $data['vehicle_type']=VehicleType::select('id','name')->get();
    $data['commodity']=Commodity::select('id','name')->get();
    $data['container_type']=ContainerType::select('id','name','code','size')->get();
    $data['piece']=Piece::select('id','name')->get();

    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

/**
* Store a newly created resource in storage.
*
* @param  \Illuminate\Http\Request  $request
* @return \Illuminate\Http\Response
*/
public function store(Request $request)
{
// dd($request);
    $request->validate([
        'customer_id' => 'required',
        'type_tarif' => 'required',
        'service_type_id' => 'required',
        'work_order_detail_id' => 'required',
        'aju_number' => 'required'
    ]);

    if ($request->service_type_id==3) {
        $return=$this->save_type_3($request);
    } else if ($request->service_type_id==1) {
        $return=$this->save_type_1($request);
    } else if ($request->service_type_id==2) {
        $return=$this->save_type_2($request);
    } else if ($request->service_type_id==4) {
        $return=$this->save_type_4($request);
    } else if ($request->service_type_id==6) {
        $return=$this->save_type_6($request);
    } else if ($request->service_type_id==7) {
        $return=$this->save_type_7($request);
    } else {
        return Response::json(['message' => 'Tipe Layanan Tidak ditemukan'],500);
    }
    return $return;
}

public function save_type_3($request)
{
// dd($request);
    $request->validate([
        'customer_id' => 'required',
        'type_tarif' => 'required',
        'service_type_id' => 'required',
        'service_id' => 'required_if:type_tarif,2',
        'quotation_detail_id' => 'required_if:type_tarif,1',
        'work_order_id' => 'required',
        'shipment_date' => 'required',
        'sender_id' => 'required',
        'receiver_id' => 'required',
        'route_id' => 'required',
        'commodity_id' => 'required',
        'vehicle_type_id' => 'required',
        'wo_customer' => 'required',
        'detail' => 'required',
        'total_unit' => 'required|integer|min:1',
        'service_id' => 'required',
        'collectible_id' => 'required',
    ]);
    if ($request->type_tarif==2) {
        $priceList=PriceList::whereRaw("service_type_id = 3 and route_id = $request->route_id and vehicle_type_id = $request->vehicle_type_id")->first();
        if (empty($priceList)) {
            return Response::json(['message' => 'Tarif Umum dengan trayek dan armada ini tidak ditemukan'],500);
        }
        $price=$priceList->price_full;
        $total_price=$request->total_unit*$priceList->price_full;
    } else {
        $quotationDetail=QuotationDetail::find($request->quotation_detail_id);
        $price=$quotationDetail->price_contract_full;
        $total_price=$request->total_unit*$quotationDetail->price_contract_full;
    }
// dd($price);
    DB::beginTransaction();
    $customer=Contact::find($request->customer_id);
    $worr=WorkOrder::find($request->work_order_id);
    if (isset($request->quotation_detail_id)) {
        $quot=QuotationDetail::find($request->quotation_detail_id);
    } else {
        $quot=null;
    }

    if ($request->work_order_id==0) {
        $code = new TransactionCode($worr->company_id, 'workOrder');
        $code->setCode();
        $trx_code = $code->getCode();
        $w=WorkOrder::create([
            'customer_id' => $request->customer_id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'code' => $trx_code,
            'total_job_order' => 1,
            'updated_by' => auth()->id()
        ]);
        $wo=$w->id;
    } else {
        WorkOrder::find($request->work_order_id)->update([
            'total_job_order' => DB::raw("total_job_order+1")
        ]);
        $wo=$request->work_order_id;
    }
    $code = new TransactionCode($worr->company_id, 'jobOrder');
    $code->setCode();
    $trx_code = $code->getCode();

    $jo=JobOrder::create([
        'company_id' => $worr->company_id,
        'customer_id' => $request->customer_id,
        'service_type_id' => $request->service_type_id,
        'service_id' => $request->service_id,
        'sender_id' => $request->sender_id,
        'receiver_id' => $request->receiver_id,
        'route_id' => $request->route_id,
        'quotation_id' => ($quot?$quot->header_id:null),
        'quotation_detail_id' => $request->quotation_detail_id,
        'work_order_id' => $wo,
        'work_order_detail_id' => $request->work_order_detail_id,
        'vehicle_type_id' => $request->vehicle_type_id,
        'commodity_id' => $request->commodity_id,
        'code' => $trx_code,
        'shipment_date' => dateDB($request->shipment_date),
        'description' => $request->description,
        'total_unit' => $request->total_unit,
        'collectible_id' => $request->collectible_id,
        'no_bl' => $request->bl_no,
        'price' => 0,
        'total_price' => 0,
        'no_po_customer' => $request->wo_customer,
        'create_by' => auth()->id(),
        'aju_number' => $request->aju_number,
        'uniqid' => str_random(100)
    ]);

    $wod=WorkOrderDetail::find($request->work_order_detail_id);
    $total=$wod->qty_leftover-$request->total_unit;
    if ($total<0) {
        return Response::json(['message' => 'Item JO tidak boleh melebihi jumlah Work Order!'],500);
    }
    $wod->update([
        'qty_leftover' => $total
    ]);

//simpan biaya Operasional di job order
    $qc=QuotationCost::where('quotation_detail_id', $request->quotation_detail_id)->get();
    if (isset($qc)) {
        foreach ($qc as $vls) {
            JobOrderCost::create([
                'header_id' => $jo->id,
                'cost_type_id' => $vls->cost_type_id,
                'transaction_type_id' => 21,
                'vendor_id' => $vls->vendor_id,
                'qty' => $request->total_unit,
                'price' => $vls->total_cost,
                'total_price' => ($vls->is_internal?0:$request->total_unit*$vls->total_cost),
                'description' => $vls->description,
                'type' => 1,
                'quotation_costs' => $request->total_unit*$vls->total_cost,
                'create_by' => auth()->id()
            ]);
        }
    } else {
        $qt=RouteCost::whereRaw("route_id = $request->route_id AND commodity_id = $request->commodity_id AND vehicle_type_id = $request->vehicle_type_id")->get();
        foreach ($qt as $qts) {
            $qtn=RouteCostDetail::where('header_id', $qts->id)->get();
            foreach ($qtn as $qtt) {
// begin
                JobOrderCost::create([
                    'header_id' => $jo->id,
                    'cost_type_id' => $qtt->cost_type_id,
                    'transaction_type_id' => 21,
                    'vendor_id' => $qtt->cost_type->vendor_id,
                    'qty' => $request->total_unit,
                    'price' => $qtt->cost,
                    'total_price' => ($qtt->is_internal?0:$qtt->cost*$request->total_unit),
                    'description' => $qtt->description,
                    'type' => 1,
                    'quotation_costs' => $qtt->cost*$request->total_unit,
                    'create_by' => auth()->id()
                ]);
// end
            }
        }
    }

    $total_item=0;
    foreach ($request->detail as $key => $value) {
        if (empty($value)) {
            continue;
        }
        JobOrderDetail::create([
// 'piece_id' => $value['piece_id'],
            'header_id' => $jo->id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'quotation_detail_id' => $request->quotation_detail_id,
            'commodity_id' => $request->commodity_id,
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'create_by' => auth()->id(),
            'is_contract' => ($quot?1:0),
            'piece_id' => $value['piece_id'],
            'price' => ($key==0?$price:0),
            'total_price' => ($key==0?$total_price:0),
            'qty' => $value['total_item'],
            'volume' => $value['total_volume'],
            'weight' => $value['total_tonase'],
            'item_name' => $value['item_name'],
            'no_reff' => $value['reff_no'],
            'no_manifest' => $value['manifest_no'],
            'description' => $value['description'],
            'transported' => $value['total_item']
        ]);
        $total_item++;
    }
    $ks=KpiStatus::whereRaw("service_id = $request->service_id and sort_number = 1")->first();
    $klog=KpiLog::create([
        'kpi_status_id' => $ks->id,
        'job_order_id' => $jo->id,
        'company_id' => $worr->company_id,
        'create_by' => auth()->id(),
        'date_update' => Carbon::now()
    ]);
    JobOrder::find($jo->id)->update([
        'kpi_id' => $klog->kpi_status_id,
        'total_item' => $total_item
    ]);

//iterasi manifest
    for ($i=0; $i < $request->total_unit; $i++) {
        $code = new TransactionCode($worr->company_id, 'manifest');
        $code->setCode();
        $mff_code = $code->getCode();

        $m=Manifest::create([
            'company_id' => $worr->company_id,
            'transaction_type_id' => 22,
            'route_id' => $request->route_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'code' => $mff_code,
            'create_by' => auth()->id(),
            'date_manifest' => createTimestamp($request->shipment_date,"00:00"),
            'is_full' => 1
        ]);
        $jod=JobOrderDetail::where('header_id', $jo->id)->get();
// dd($jod);
        foreach ($jod as $key => $value) {
            ManifestDetail::create([
                'header_id' => $m->id,
                'create_by' => auth()->id(),
                'update_by' => auth()->id(),
                'job_order_detail_id' => $value->id,
                'transported' => ($i==0?$value->qty:0)
            ]);

        }
    }
    DB::commit();

    return Response::json(null);
}
public function save_type_4($request)
{
// dd($request);
    $request->validate([
        'customer_id' => 'required',
        'type_tarif' => 'required',
        'service_type_id' => 'required',
        'service_id' => 'required_if:type_tarif,2',
        'quotation_detail_id' => 'required_if:type_tarif,1',
        'work_order_id' => 'required',
        'shipment_date' => 'required',
        'sender_id' => 'required',
        'receiver_id' => 'required',
        'route_id' => 'required',
        'commodity_id' => 'required',
        'vehicle_type_id' => 'required',
        'wo_customer' => 'required',
        'detail' => 'required',
        'total_unit' => 'required|integer|min:1',
        'service_id' => 'required',
        'collectible_id' => 'required',
    ]);
    if ($request->type_tarif==2) {
        $priceList=PriceList::whereRaw("service_type_id = 4 and route_id = $request->route_id and vehicle_type_id = $request->vehicle_type_id")->first();
        if (empty($priceList)) {
            return Response::json(['message' => 'Tarif Umum dengan trayek dan armada ini tidak ditemukan'],500);
        }
        $price=$priceList->price_full;
        $total_price=$request->total_unit*$priceList->price_full;
    } else {
        $quotationDetail=QuotationDetail::find($request->quotation_detail_id);
        $price=$quotationDetail->price_contract_full;
        $total_price=$request->total_unit*$quotationDetail->price_contract_full;
    }
// dd($price);
    DB::beginTransaction();
    $customer=Contact::find($request->customer_id);
    $worr=WorkOrder::find($request->work_order_id);
    if (isset($request->quotation_detail_id)) {
        $quot=QuotationDetail::find($request->quotation_detail_id);
    } else {
        $quot=null;
    }

    if ($request->work_order_id==0) {
        $code = new TransactionCode($worr->company_id, 'workOrder');
        $code->setCode();
        $trx_code = $code->getCode();
        $w=WorkOrder::create([
            'customer_id' => $request->customer_id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'code' => $trx_code,
            'total_job_order' => 1,
            'updated_by' => auth()->id(),
            'price_list_id' => $priceList->id??null,
            'quotation_detail_id' => $quotationDetail->id??null,
        ]);
        $wo=$w->id;
    } else {
        WorkOrder::find($request->work_order_id)->update([
            'total_job_order' => DB::raw("total_job_order+1")
        ]);
        $wo=$request->work_order_id;
    }
    $code = new TransactionCode($worr->company_id, 'jobOrder');
    $code->setCode();
    $trx_code = $code->getCode();

    $jo=JobOrder::create([
        'company_id' => $worr->company_id,
        'customer_id' => $request->customer_id,
        'service_type_id' => $request->service_type_id,
        'service_id' => $request->service_id,
        'sender_id' => $request->sender_id,
        'receiver_id' => $request->receiver_id,
        'route_id' => $request->route_id,
        'quotation_id' => ($quot?$quot->header_id:null),
        'quotation_detail_id' => $request->quotation_detail_id,
        'work_order_id' => $wo,
        'work_order_detail_id' => $request->work_order_detail_id,
        'vehicle_type_id' => $request->vehicle_type_id,
        'commodity_id' => $request->commodity_id,
        'code' => $trx_code,
        'shipment_date' => dateDB($request->shipment_date),
        'description' => $request->description,
        'total_unit' => $request->total_unit,
        'collectible_id' => $request->collectible_id,
        'no_bl' => $request->bl_no,
        'price' => 0,
        'total_price' => 0,
        'no_po_customer' => $request->wo_customer,
        'create_by' => auth()->id(),
        'aju_number' => $request->aju_number,
        'uniqid' => str_random(100)
    ]);

    $total_item=0;
    foreach ($request->detail as $key => $value) {
        JobOrderDetail::create([
// 'piece_id' => $value['piece_id'],
            'header_id' => $jo->id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'quotation_detail_id' => $request->quotation_detail_id,
            'commodity_id' => $request->commodity_id,
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'create_by' => auth()->id(),
            'is_contract' => ($quot?1:0),
            'piece_id' => $value['piece_id'],
            'price' => ($key==0?$price:0),
            'total_price' => ($key==0?$total_price:0),
            'qty' => $value['total_item'],
            'volume' => $value['total_volume'],
            'weight' => $value['total_tonase'],
            'item_name' => $value['item_name'],
            'no_reff' => $value['reff_no'],
            'no_manifest' => $value['manifest_no'],
            'description' => $value['description'],
            'transported' => $value['total_item']
        ]);
        $total_item++;
    }
    $ks=KpiStatus::whereRaw("service_id = $request->service_id and sort_number = 1")->first();
    $klog=KpiLog::create([
        'kpi_status_id' => $ks->id,
        'job_order_id' => $jo->id,
        'company_id' => $worr->company_id,
        'create_by' => auth()->id(),
        'date_update' => Carbon::now()
    ]);
    JobOrder::find($jo->id)->update([
        'kpi_id' => $klog->kpi_status_id,
        'total_item' => $total_item
    ]);

//simpan biaya Operasional di job order
    $qc=QuotationCost::where('quotation_detail_id', $request->quotation_detail_id)->get();
    if (isset($qc)) {
        foreach ($qc as $vls) {
            JobOrderCost::create([
                'header_id' => $jo->id,
                'cost_type_id' => $vls->cost_type_id,
                'transaction_type_id' => 21,
                'vendor_id' => $vls->vendor_id,
                'qty' => $request->total_unit,
                'price' => $vls->total_cost,
                'total_price' => ($vls->is_internal?0:$request->total_unit*$vls->total_cost),
                'description' => $vls->description,
                'type' => 1,
                'quotation_costs' => $request->total_unit*$vls->total_cost,
                'create_by' => auth()->id()
            ]);
        }
    } else {
        $qt=RouteCost::whereRaw("route_id = $request->route_id AND commodity_id = $request->commodity_id AND vehicle_type_id = $request->vehicle_type_id")->get();
        foreach ($qt as $qts) {
            $qtn=RouteCostDetail::where('header_id', $qts->id)->get();
            foreach ($qtn as $qtt) {
// begin
                JobOrderCost::create([
                    'header_id' => $jo->id,
                    'cost_type_id' => $qtt->cost_type_id,
                    'transaction_type_id' => 21,
                    'vendor_id' => $qtt->cost_type->vendor_id,
                    'qty' => $request->total_unit,
                    'price' => $qtt->cost,
                    'total_price' => ($qtt->is_internal?0:$qtt->cost*$request->total_unit),
                    'description' => $qtt->description,
                    'type' => 1,
                    'quotation_costs' => $qtt->cost*$request->total_unit,
                    'create_by' => auth()->id()
                ]);
// end
            }
        }
    }

//iterasi manifest
    for ($i=0; $i < $request->total_unit; $i++) {
        $code = new TransactionCode($worr->company_id, 'manifest');
        $code->setCode();
        $mff_code = $code->getCode();

        $m=Manifest::create([
            'company_id' => $worr->company_id,
            'transaction_type_id' => 22,
            'route_id' => $request->route_id,
            'vehicle_type_id' => $request->vehicle_type_id,
            'code' => $mff_code,
            'create_by' => auth()->id(),
            'date_manifest' => createTimestamp($request->shipment_date,"00:00"),
            'is_full' => 1
        ]);
        $jod=JobOrderDetail::where('header_id', $jo->id)->get();
// dd($jod);
        foreach ($jod as $key => $value) {
            ManifestDetail::create([
                'header_id' => $m->id,
                'create_by' => auth()->id(),
                'update_by' => auth()->id(),
                'job_order_detail_id' => $value->id,
                'transported' => ($i==0?$value->qty:0)
            ]);

        }
    }
    DB::commit();

    return Response::json(null);
}
public function save_type_1($request)
{
// dd($request);
    $request->validate([
        'customer_id' => 'required',
        'type_tarif' => 'required',
        'service_type_id' => 'required',
        'service_id' => 'required_if:type_tarif,2',
        'quotation_detail_id' => 'required_if:type_tarif,1',
        'work_order_id' => 'required',
        'shipment_date' => 'required',
        'sender_id' => 'required',
        'receiver_id' => 'required',
        'route_id' => 'required',
        'moda_id' => 'required',
        'commodity_id' => 'required',
        'wo_customer' => 'required',
        'detail' => 'required',
        'service_id' => 'required',
        'collectible_id' => 'required',
    ],[
        'service_id.required_if' => 'Jenis Layanan harus dipilih jika tipe tarif dari tarif umum'
    ]);
// dd($price);
    DB::beginTransaction();
    $customer=Contact::find($request->customer_id);
    $worr=WorkOrder::find($request->work_order_id);
    if (isset($request->quotation_detail_id)) {
        $quot=QuotationDetail::find($request->quotation_detail_id);
    } else {
        $quot=null;
    }

    if ($request->work_order_id==0) {
        $code = new TransactionCode($worr->company_id, 'workOrder');
        $code->setCode();
        $trx_code = $code->getCode();
        $w=WorkOrder::create([
            'customer_id' => $request->customer_id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'code' => $trx_code,
            'total_job_order' => 1,
            'updated_by' => auth()->id()
        ]);
        $wo=$w->id;
    } else {
        WorkOrder::find($request->work_order_id)->update([
            'total_job_order' => DB::raw("total_job_order+1")
        ]);
        $wo=$request->work_order_id;
    }
    $code = new TransactionCode($worr->company_id, 'jobOrder');
    $code->setCode();
    $trx_code = $code->getCode();

    $jo=JobOrder::create([
        'company_id' => $worr->company_id,
        'customer_id' => $request->customer_id,
        'service_type_id' => $request->service_type_id,
        'service_id' => $request->service_id,
        'sender_id' => $request->sender_id,
        'receiver_id' => $request->receiver_id,
        'route_id' => $request->route_id,
        'quotation_id' => ($quot?$quot->header_id:null),
        'quotation_detail_id' => $request->quotation_detail_id,
        'work_order_id' => $wo,
        'work_order_detail_id' => $request->work_order_detail_id,
        'vehicle_type_id' => $request->vehicle_type_id,
        'container_type_id' => $request->container_type_id,
        'commodity_id' => $request->commodity_id,
        'code' => $trx_code,
        'shipment_date' => dateDB($request->shipment_date),
        'description' => $request->description,
        'moda_id' => $request->moda_id,
        'collectible_id' => $request->collectible_id,
        'no_bl' => $request->bl_no,
        'price' => 0,
        'total_price' => 0,
        'no_po_customer' => $request->wo_customer,
        'create_by' => auth()->id(),
        'aju_number' => $request->aju_number,
        'uniqid' => str_random(100)
    ]);

//simpan biaya Operasional di job order
    $qc=QuotationCost::where('quotation_detail_id', $request->quotation_detail_id)->get();
    if (isset($qc)) {
        foreach ($qc as $vls) {
            JobOrderCost::create([
                'header_id' => $jo->id,
                'cost_type_id' => $vls->cost_type_id,
                'transaction_type_id' => 21,
                'vendor_id' => $vls->vendor_id,
                'qty' => $request->total_unit,
                'price' => $vls->total_cost,
                'total_price' => ($vls->is_internal?0:$request->total_unit*$vls->total_cost),
                'description' => $vls->description,
                'type' => 1,
                'quotation_costs' => $request->total_unit*$vls->total_cost,
                'create_by' => auth()->id()
            ]);
        }
    }

    $total_item=0;
    foreach ($request->detail as $key => $value) {
        if (empty($value)) {
            continue;
        }
        if ($request->type_tarif==2) {
            $priceList=PriceList::whereRaw("service_type_id = 1 and moda_id = $request->moda_id and route_id = $request->route_id and vehicle_type_id = $request->vehicle_type_id")->first();
            if (empty($priceList)) {
                return Response::json(['message' => 'Tarif Umum dengan trayek dan armada ini tidak ditemukan'],500);
            }
            if ($value['imposition']==1) {
                $price=$priceList->price_volume;
                $total_price=$price*$value['total_volume'];
            } elseif ($value['imposition']==2) {
                $price=$priceList->price_tonase;
                $total_price=$price*$value['total_tonase'];
            } else {
                $price=$priceList->price_item;
                $total_price=$price*$value['total_item'];
            }
// $total_price=*$price;
        } else {
            $quotationDetail=QuotationDetail::find($request->quotation_detail_id);
            if ($value['imposition']==1) {
                $price=$quotationDetail->price_contract_volume;
                $total_price=$price*$value['total_volume'];
            } elseif ($value['imposition']==2) {
                $price=$quotationDetail->price_contract_tonase;
                $total_price=$price*$value['total_tonase'];
            } else {
                $price=$quotationDetail->price_contract_item;
                $total_price=$price*$value['total_item'];
            }
        }

        JobOrderDetail::create([
// 'piece_id' => $value['piece_id'],
            'header_id' => $jo->id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'quotation_detail_id' => $request->quotation_detail_id,
            'commodity_id' => $request->commodity_id,
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'create_by' => auth()->id(),
            'is_contract' => ($quot?1:0),
            'piece_id' => $value['piece_id'],
            'price' => $price,
            'total_price' => $total_price,
            'qty' => $value['total_item'],
            'volume' => $value['total_volume'],
            'weight' => $value['total_tonase'],
            'item_name' => $value['item_name'],
            'imposition' => $value['imposition'],
            'description' => $value['description'],
            'leftover' => $value['total_item']
        ]);
        $total_item++;
        $wod=WorkOrderDetail::find($request->work_order_detail_id);;
        if ($value['imposition']==1) {
            $total=$wod->qty_leftover-$value['total_volume'];
            if ($total<0) {
                return Response::json(['message' => 'Jumlah Item JO tidak boleh melebihi Work Order!'],500);
            }
            $wod->update([
                'qty_leftover' => $total
            ]);
        } elseif ($value['imposition']==2) {
            $total=$wod->qty_leftover-$value['total_tonase'];
            if ($total<0) {
                return Response::json(['message' => 'Jumlah Item JO tidak boleh melebihi Work Order!'],500);
            }
            $wod->update([
                'qty_leftover' => $total
            ]);
        } else {
            $total=$wod->qty_leftover-$value['total_item'];
// dd($request->work_order_detail_id);
            if ($total<0) {
                return Response::json(['message' => 'Jumlah Item JO tidak boleh melebihi Work Order!'],500);
            }
            $wod->update([
                'qty_leftover' => $total
            ]);
        }
    }
    $ks=KpiStatus::whereRaw("service_id = $request->service_id and sort_number = 1")->first();
    $klog=KpiLog::create([
        'kpi_status_id' => $ks->id,
        'job_order_id' => $jo->id,
        'company_id' => $worr->company_id,
        'create_by' => auth()->id(),
        'date_update' => Carbon::now()
    ]);
    JobOrder::find($jo->id)->update([
        'kpi_id' => $klog->kpi_status_id,
        'total_item' => $total_item
    ]);

    DB::commit();

    return Response::json(null);
}
public function save_type_2($request)
{
// dd($request);
    $request->validate([
        'customer_id' => 'required',
        'type_tarif' => 'required',
        'service_type_id' => 'required',
        'service_id' => 'required_if:type_tarif,2',
        'quotation_detail_id' => 'required_if:type_tarif,1',
        'work_order_id' => 'required',
        'shipment_date' => 'required',
        'sender_id' => 'required',
        'receiver_id' => 'required',
        'route_id' => 'required',
        'commodity_id' => 'required',
        'container_type_id' => 'required',
        'wo_customer' => 'required',
        'detail' => 'required',
        'total_unit' => 'required|integer|min:1',
        'service_id' => 'required',
        'collectible_id' => 'required',
    ]);
    if ($request->type_tarif==2) {
        $priceList=PriceList::whereRaw("service_type_id = 2 and route_id = $request->route_id and commodity_id = $request->commodity_id and container_type_id = $request->container_type_id")->first();
        if (empty($priceList)) {
            return Response::json(['message' => 'Tarif Umum dengan trayek, tipe kontainer, dan komoditas ini tidak ditemukan'],500);
        }
        $price=$priceList->price_full;
        $total_price=$request->total_unit*$priceList->price_full;
    } else {
        $quotationDetail=QuotationDetail::find($request->quotation_detail_id);
        $price=$quotationDetail->price_contract_full;
        $total_price=$request->total_unit*$quotationDetail->price_contract_full;
    }
// dd($price);
    DB::beginTransaction();
    $customer=Contact::find($request->customer_id);
    $worr=WorkOrder::find($request->work_order_id);
    if (isset($request->quotation_detail_id)) {
        $quot=QuotationDetail::find($request->quotation_detail_id);
    } else {
        $quot=null;
    }

    if ($request->work_order_id==0) {
        $code = new TransactionCode($worr->company_id, 'workOrder');
        $code->setCode();
        $trx_code = $code->getCode();
        $w=WorkOrder::create([
            'customer_id' => $request->customer_id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'code' => $trx_code,
            'total_job_order' => 1,
            'updated_by' => auth()->id()
        ]);
        $wo=$w->id;
    } else {
        WorkOrder::find($request->work_order_id)->update([
            'total_job_order' => DB::raw("total_job_order+1")
        ]);
        $wo=$request->work_order_id;
    }
    $code = new TransactionCode($worr->company_id, 'jobOrder');
    $code->setCode();
    $trx_code = $code->getCode();

    $jo=JobOrder::create([
        'company_id' => $worr->company_id,
        'customer_id' => $request->customer_id,
        'service_type_id' => $request->service_type_id,
        'service_id' => $request->service_id,
        'sender_id' => $request->sender_id,
        'receiver_id' => $request->receiver_id,
        'route_id' => $request->route_id,
        'quotation_id' => ($quot?$quot->header_id:null),
        'quotation_detail_id' => $request->quotation_detail_id,
        'work_order_id' => $wo,
        'work_order_detail_id' => $request->work_order_detail_id,
        'container_type_id' => $request->container_type_id,
        'commodity_id' => $request->commodity_id,
        'code' => $trx_code,
        'shipment_date' => dateDB($request->shipment_date),
        'description' => $request->description,
        'total_unit' => $request->total_unit,
        'collectible_id' => $request->collectible_id,
        'no_bl' => $request->bl_no,
        'price' => 0,
        'total_price' => 0,
        'no_po_customer' => $request->wo_customer,
        'create_by' => auth()->id(),
        'aju_number' => $request->aju_number,
        'uniqid' => str_random(100)
    ]);

    $wod=WorkOrderDetail::find($request->work_order_detail_id);
    $total=$wod->qty_leftover-$request->total_unit;
    if ($total<0) {
        return Response::json(['message' => 'Item JO tidak boleh melebihi jumlah Work Order!'],500);
    }
    $wod->update([
        'qty_leftover' => $total
    ]);

    $total_item=0;
    foreach ($request->detail as $key => $value) {
        if (empty($value)) {
            continue;
        }
        JobOrderDetail::create([
// 'piece_id' => $value['piece_id'],
            'header_id' => $jo->id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'quotation_detail_id' => $request->quotation_detail_id,
            'commodity_id' => $request->commodity_id,
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'create_by' => auth()->id(),
            'is_contract' => ($quot?1:0),
            'piece_id' => $value['piece_id'],
            'price' => ($key==0?$price:0),
            'total_price' => ($key==0?$total_price:0),
            'qty' => $value['total_item'],
            'volume' => $value['total_volume'],
            'weight' => $value['total_tonase'],
            'item_name' => $value['item_name'],
            'no_reff' => $value['reff_no'],
            'no_manifest' => $value['manifest_no'],
            'description' => $value['description'],
            'transported' => $value['total_item']
        ]);
        $total_item++;
    }
    $ks=KpiStatus::whereRaw("service_id = $request->service_id and sort_number = 1")->first();
    $klog=KpiLog::create([
        'kpi_status_id' => $ks->id,
        'job_order_id' => $jo->id,
        'company_id' => $worr->company_id,
        'create_by' => auth()->id(),
        'date_update' => Carbon::now()
    ]);
    JobOrder::find($jo->id)->update([
        'kpi_id' => $klog->kpi_status_id,
        'total_item' => $total_item
    ]);

//simpan biaya Operasional di job order
    $qc=QuotationCost::where('quotation_detail_id', $request->quotation_detail_id)->get();
    if (isset($qc)) {
        foreach ($qc as $vls) {
            JobOrderCost::create([
                'header_id' => $jo->id,
                'cost_type_id' => $vls->cost_type_id,
                'transaction_type_id' => 21,
                'vendor_id' => $vls->vendor_id,
                'qty' => $request->total_unit,
                'price' => $vls->total_cost,
                'total_price' => ($vls->is_internal?0:$request->total_unit*$vls->total_cost),
                'description' => $vls->description,
                'type' => 1,
                'quotation_costs' => $request->total_unit*$vls->total_cost,
                'create_by' => auth()->id()
            ]);
        }
    } else {
        $qt=RouteCost::whereRaw("route_id = $request->route_id AND commodity_id = $request->commodity_id AND container_type_id = $request->container_type_id")->get();
        foreach ($qt as $qts) {
            $qtn=RouteCostDetail::where('header_id', $qts->id)->get();
            foreach ($qtn as $qtt) {
// begin
                JobOrderCost::create([
                    'header_id' => $jo->id,
                    'cost_type_id' => $qtt->cost_type_id,
                    'transaction_type_id' => 21,
                    'vendor_id' => $qtt->cost_type->vendor_id,
                    'qty' => $request->total_unit,
                    'price' => $qtt->cost,
                    'total_price' => ($qtt->is_internal?0:$qtt->cost*$request->total_unit),
                    'description' => $qtt->description,
                    'type' => 1,
                    'quotation_costs' => $qtt->cost*$request->total_unit,
                    'create_by' => auth()->id()
                ]);
// end
            }
        }
    }

//iterasi manifest
    for ($i=0; $i < $request->total_unit; $i++) {
        $code = new TransactionCode($worr->company_id, 'manifest');
        $code->setCode();
        $mff_code = $code->getCode();

        $m=Manifest::create([
            'company_id' => $worr->company_id,
            'transaction_type_id' => 22,
            'route_id' => $request->route_id,
            'container_type_id' => $request->container_type_id,
            'code' => $mff_code,
            'create_by' => auth()->id(),
            'date_manifest' => createTimestamp($request->shipment_date,"00:00"),
            'is_full' => 1,
            'is_container' => 1
        ]);
        $jod=JobOrderDetail::where('header_id', $jo->id)->get();
// dd($jod);
        foreach ($jod as $key => $value) {
            ManifestDetail::create([
                'header_id' => $m->id,
                'create_by' => auth()->id(),
                'update_by' => auth()->id(),
                'job_order_detail_id' => $value->id,
                'transported' => ($i==0?$value->qty:0)
            ]);

        }
    }
    DB::commit();

    return Response::json(null);
}
public function save_type_6($request)
{
// dd($request);
    $request->validate([
        'customer_id' => 'required',
        'type_tarif' => 'required',
        'service_type_id' => 'required',
        'service_id' => 'required_if:type_tarif,2',
        'quotation_detail_id' => 'required_if:type_tarif,1',
        'work_order_id' => 'required',
        'shipment_date' => 'required',
        'receiver_id' => 'required',
        'reff_no' => 'required',
        'docs_no' => 'required',
        'docs_reff_no' => 'required',
        'piece_id' => 'required',
        'wo_customer' => 'required',
        'service_id' => 'required',
        'collectible_id' => 'required',
        'item_name' => 'required',
        'document_name' => 'required',
        'total_unit' => 'required',
    ]);
// dd($price);
    DB::beginTransaction();
    $customer=Contact::find($request->customer_id);
    $worr=WorkOrder::find($request->work_order_id);
    if (isset($request->quotation_detail_id)) {
        $quot=QuotationDetail::find($request->quotation_detail_id);
    } else {
        $quot=null;
    }

    if ($request->work_order_id==0) {
        $code = new TransactionCode($worr->company_id, 'workOrder');
        $code->setCode();
        $trx_code = $code->getCode();
        $w=WorkOrder::create([
            'customer_id' => $request->customer_id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'code' => $trx_code,
            'total_job_order' => 1,
            'updated_by' => auth()->id()
        ]);
        $wo=$w->id;
    } else {
        WorkOrder::find($request->work_order_id)->update([
            'total_job_order' => DB::raw("total_job_order+1")
        ]);
        $wo=$request->work_order_id;
    }
    $code = new TransactionCode($worr->company_id, 'jobOrder');
    $code->setCode();
    $trx_code = $code->getCode();

    $jo=JobOrder::create([
        'company_id' => $worr->company_id,
        'customer_id' => $request->customer_id,
        'service_type_id' => $request->service_type_id,
        'service_id' => $request->service_id,
        'receiver_id' => $request->receiver_id,
        'reff_no' => $request->reff_no,
        'quotation_id' => ($quot?$quot->header_id:null),
        'quotation_detail_id' => $request->quotation_detail_id,
        'work_order_id' => $wo,
        'work_order_detail_id' => $request->work_order_detail_id,
        'docs_no' => $request->docs_no,
        'docs_reff_no' => $request->docs_reff_no,
        'code' => $trx_code,
        'shipment_date' => dateDB($request->shipment_date),
        'description' => $request->description,
        'piece_id' => $request->piece_id,
        'collectible_id' => $request->collectible_id,
        'no_bl' => $request->bl_no,
        'price' => 0,
        'total_price' => 0,
        'no_po_customer' => $request->wo_customer,
        'create_by' => auth()->id(),
        'aju_number' => $request->aju_number,
        'vessel_name' => $request->vessel_name,
        'voyage_no' => $request->voyage_no,
        'document_name' => $request->document_name,
        'uniqid' => str_random(100)
    ]);

    $wod=WorkOrderDetail::find($request->work_order_detail_id);
    $total=$wod->qty_leftover-$request->total_unit;
    if ($total<0) {
        return Response::json(['message' => 'Item JO tidak boleh melebihi jumlah Work Order!'],500);
    }
    $wod->update([
        'qty_leftover' => $total
    ]);

    if ($quot) {
//jika ada quotation
        $qc=QuotationCost::where('quotation_detail_id', $quot->id)->get();
        foreach ($qc as $vlz) {
            $slug=str_random(6);
            $ctt=CostType::find($vlz->cost_type_id);
            $jc=JobOrderCost::create([
                'header_id' => $jo->id,
                'cost_type_id' => $vlz->cost_type_id,
                'transaction_type_id' => 21,
                'vendor_id' => $vlz->vendor_id,
                'qty' => $vlz->total,
                'price' => $vlz->cost,
                'total_price' => 0,
                'description' => $vlz->description,
                'create_by' => auth()->id(),
                'quotation_costs' => $vlz->total_cost,
                'status' => 1,
                'slug' => $slug
            ]);

// notif----------------------------------------------
// $percent=($jc->total_price-$jc->quotation_costs)/$jc->quotation_costs*100;
// if ($jc->total_price <= $jc->quotation_costs) {
//   $userList=DB::table('notification_type_users')
//   ->leftJoin('users','users.id','=','notification_type_users.user_id')
//   ->whereRaw("notification_type_users.notification_type_id = 6")
//   ->select('users.id','users.is_admin','users.company_id')->get();
//   $n=Notification::create([
//     'notification_type_id' => 6,
//     'name' => 'Ada Biaya Job Order Baru yang memerlukan persetujuan Supervisi!',
//     'description' => 'No. JO '.$jo->code.' pada biaya '.$ctt->name,
//     'slug' => $slug,
//     'route' => 'operational.job_order.show',
//     'parameter' => json_encode(['id' => $jo->id])
//   ]);
//   foreach ($userList as $un) {
//     if ($un->is_admin) {
//       NotificationUser::create([
//         'notification_id' => $n->id,
//         'user_id' => $un->id
//       ]);
//     } else {
//       if ($un->company_id==auth()->user()->company_id) {
//         NotificationUser::create([
//           'notification_id' => $n->id,
//           'user_id' => $un->id
//         ]);
//       }
//       //abaikan
//     }
//   }
// } elseif ($percent <= 5) {
//   $userList=DB::table('notification_type_users')
//   ->leftJoin('users','users.id','=','notification_type_users.user_id')
//   ->whereRaw("notification_type_users.notification_type_id = 7")
//   ->select('users.id','users.is_admin','users.company_id')->get();
//   $n=Notification::create([
//     'notification_type_id' => 7,
//     'name' => 'Ada Biaya Job Order Baru yang memerlukan persetujuan Manajer!',
//     'description' => 'No. JO '.$jo->code.' pada biaya '.$ctt->name,
//     'slug' => $slug,
//     'route' => 'operational.job_order.show',
//     'parameter' => json_encode(['id' => $jo->id])
//   ]);
//   foreach ($userList as $un) {
//     if ($un->is_admin) {
//       NotificationUser::create([
//         'notification_id' => $n->id,
//         'user_id' => $un->id
//       ]);
//     } else {
//       if ($un->company_id==auth()->user()->company_id) {
//         NotificationUser::create([
//           'notification_id' => $n->id,
//           'user_id' => $un->id
//         ]);
//       }
//       //abaikan
//     }
//   }
// } else {
//   $userList=DB::table('notification_type_users')
//   ->leftJoin('users','users.id','=','notification_type_users.user_id')
//   ->whereRaw("notification_type_users.notification_type_id = 8")
//   ->select('users.id','users.is_admin','users.company_id')->get();
//   $n=Notification::create([
//     'notification_type_id' => 8,
//     'name' => 'Ada Biaya Job Order Baru yang memerlukan persetujuan Direksi!',
//     'description' => 'No. JO '.$jo->code.' pada biaya '.$ctt->name,
//     'slug' => $slug,
//     'route' => 'operational.job_order.show',
//     'parameter' => json_encode(['id' => $jo->id])
//   ]);
//   foreach ($userList as $un) {
//     if ($un->is_admin) {
//       NotificationUser::create([
//         'notification_id' => $n->id,
//         'user_id' => $un->id
//       ]);
//     } else {
//       if ($un->company_id==auth()->user()->company_id) {
//         NotificationUser::create([
//           'notification_id' => $n->id,
//           'user_id' => $un->id
//         ]);
//       }
//       //abaikan
//     }
//   }
// }
// end notif---------------------------------------------
        }
    }

    $total_item=1;
    if ($request->type_tarif==2) {
        $priceList=PriceList::whereRaw("service_type_id = 6 and piece_id = $request->piece_id ")->first();
        if (empty($priceList)) {
            return Response::json(['message' => 'Tarif Umum dengan Layanan ini tidak ditemukan'],500);
        }
        $price=$priceList->price_full;
        $total_price=$request->total_unit*$priceList->price_full;
// $total_price=*$price;
    } else {
        $quotationDetail=QuotationDetail::find($request->quotation_detail_id);
        $price=$quotationDetail->price_contract_full;
        $total_price=$request->total_unit*$quotationDetail->price_contract_full;
    }

    JobOrderDetail::create([
// 'piece_id' => $value['piece_id'],
        'header_id' => $jo->id,
        'quotation_id' => ($quot?$quot->header_id:null),
        'quotation_detail_id' => $request->quotation_detail_id,
        'receiver_id' => $request->receiver_id,
        'create_by' => auth()->id(),
        'is_contract' => ($quot?1:0),
        'piece_id' => $request->piece_id,
        'price' => $price,
        'total_price' => $total_price,
        'qty' => $request->total_unit,
        'item_name' => $request->item_name,
        'imposition' => 1,
    ]);

    $ks=KpiStatus::whereRaw("service_id = $request->service_id and sort_number = 1")->first();
    $klog=KpiLog::create([
        'kpi_status_id' => $ks->id,
        'job_order_id' => $jo->id,
        'company_id' => $worr->company_id,
        'create_by' => auth()->id(),
        'date_update' => Carbon::now()
    ]);
    JobOrder::find($jo->id)->update([
        'kpi_id' => $klog->kpi_status_id,
        'total_item' => $total_item
    ]);

    DB::commit();

    return Response::json(null);
}
public function save_type_7($request)
{
// dd($request);
    $request->validate([
        'customer_id' => 'required',
        'type_tarif' => 'required',
        'service_type_id' => 'required',
        'service_id' => 'required_if:type_tarif,2',
        'quotation_detail_id' => 'required_if:type_tarif,1',
        'work_order_id' => 'required',
        'shipment_date' => 'required',
        'receiver_id' => 'required',
        'reff_no' => 'required',
        'piece_id' => 'required',
        'wo_customer' => 'required',
        'service_id' => 'required',
//'collectible_id' => 'required',
        'total_unit' => 'required',
    ]);
// dd($price);
    DB::beginTransaction();
    $customer=Contact::find($request->customer_id);
    $worr=WorkOrder::find($request->work_order_id);
    if (isset($request->quotation_detail_id)) {
        $quot=QuotationDetail::find($request->quotation_detail_id);
    } else {
        $quot=null;
    }

    if ($request->work_order_id==0) {
        $code = new TransactionCode($worr->company_id, 'workOrder');
        $code->setCode();
        $trx_code = $code->getCode();
        $w=WorkOrder::create([
            'customer_id' => $request->customer_id,
            'quotation_id' => ($quot?$quot->header_id:null),
            'code' => $trx_code,
            'total_job_order' => 1,
            'updated_by' => auth()->id()
        ]);
        $wo=$w->id;
    } else {
        WorkOrder::find($request->work_order_id)->update([
            'total_job_order' => DB::raw("total_job_order+1")
        ]);
        $wo=$request->work_order_id;
    }
    $code = new TransactionCode($worr->company_id, 'jobOrder');
    $code->setCode();
    $trx_code = $code->getCode();

    $jo=JobOrder::create([
        'company_id' => $worr->company_id,
        'customer_id' => $request->customer_id,
        'service_type_id' => $request->service_type_id,
        'service_id' => $request->service_id,
        'receiver_id' => $request->receiver_id,
        'reff_no' => $request->reff_no,
        'quotation_id' => ($quot?$quot->header_id:null),
        'quotation_detail_id' => $request->quotation_detail_id,
        'work_order_id' => $wo,
        'work_order_detail_id' => $request->work_order_detail_id,
        'code' => $trx_code,
        'shipment_date' => dateDB($request->shipment_date),
        'description' => $request->description,
        'piece_id' => $request->piece_id,
        'collectible_id' => $request->collectible_id,
        'no_bl' => $request->bl_no,
        'price' => 0,
        'total_price' => 0,
        'no_po_customer' => $request->wo_customer,
        'create_by' => auth()->id(),
        'aju_number' => $request->aju_number,
        'uniqid' => str_random(100)
    ]);

    $wod=WorkOrderDetail::find($request->work_order_detail_id);
    $total=$wod->qty_leftover-$request->total_unit;
    if ($total<0) {
        return Response::json(['message' => 'Item JO tidak boleh melebihi jumlah Work Order!'],500);
    }
    $wod->update([
        'qty_leftover' => $total
    ]);

    if ($quot) {
//jika ada quotation
        $qc=QuotationCost::where('quotation_detail_id', $quot->id)->get();
        foreach ($qc as $vlz) {
            $slug=str_random(6);
            $ctt=CostType::find($vlz->cost_type_id);
            $jc=JobOrderCost::create([
                'header_id' => $jo->id,
                'cost_type_id' => $vlz->cost_type_id,
                'transaction_type_id' => 21,
                'vendor_id' => $vlz->vendor_id,
                'qty' => $vlz->total,
                'price' => $vlz->cost,
                'total_price' => 0,
                'description' => $vlz->description,
                'create_by' => auth()->id(),
                'quotation_costs' => $vlz->total_cost,
                'status' => 1,
                'slug' => $slug
            ]);
// notif----------------------------------------------
// $percent=($jc->total_price-$jc->quotation_costs)/$jc->quotation_costs*100;
// if ($jc->total_price <= $jc->quotation_costs) {
//   $userList=DB::table('notification_type_users')
//   ->leftJoin('users','users.id','=','notification_type_users.user_id')
//   ->whereRaw("notification_type_users.notification_type_id = 6")
//   ->select('users.id','users.is_admin','users.company_id')->get();
//   $n=Notification::create([
//     'notification_type_id' => 6,
//     'name' => 'Ada Biaya Job Order Baru yang memerlukan persetujuan Supervisi!',
//     'description' => 'No. JO '.$jo->code.' pada biaya '.$ctt->name,
//     'slug' => $slug,
//     'route' => 'operational.job_order.show',
//     'parameter' => json_encode(['id' => $jo->id])
//   ]);
//   foreach ($userList as $un) {
//     if ($un->is_admin) {
//       NotificationUser::create([
//         'notification_id' => $n->id,
//         'user_id' => $un->id
//       ]);
//     } else {
//       if ($un->company_id==auth()->user()->company_id) {
//         NotificationUser::create([
//           'notification_id' => $n->id,
//           'user_id' => $un->id
//         ]);
//       }
//       //abaikan
//     }
//   }
// } elseif ($percent <= 5) {
//   $userList=DB::table('notification_type_users')
//   ->leftJoin('users','users.id','=','notification_type_users.user_id')
//   ->whereRaw("notification_type_users.notification_type_id = 7")
//   ->select('users.id','users.is_admin','users.company_id')->get();
//   $n=Notification::create([
//     'notification_type_id' => 7,
//     'name' => 'Ada Biaya Job Order Baru yang memerlukan persetujuan Manajer!',
//     'description' => 'No. JO '.$jo->code.' pada biaya '.$ctt->name,
//     'slug' => $slug,
//     'route' => 'operational.job_order.show',
//     'parameter' => json_encode(['id' => $jo->id])
//   ]);
//   foreach ($userList as $un) {
//     if ($un->is_admin) {
//       NotificationUser::create([
//         'notification_id' => $n->id,
//         'user_id' => $un->id
//       ]);
//     } else {
//       if ($un->company_id==auth()->user()->company_id) {
//         NotificationUser::create([
//           'notification_id' => $n->id,
//           'user_id' => $un->id
//         ]);
//       }
//       //abaikan
//     }
//   }
// } else {
//   $userList=DB::table('notification_type_users')
//   ->leftJoin('users','users.id','=','notification_type_users.user_id')
//   ->whereRaw("notification_type_users.notification_type_id = 8")
//   ->select('users.id','users.is_admin','users.company_id')->get();
//   $n=Notification::create([
//     'notification_type_id' => 8,
//     'name' => 'Ada Biaya Job Order Baru yang memerlukan persetujuan Direksi!',
//     'description' => 'No. JO '.$jo->code.' pada biaya '.$ctt->name,
//     'slug' => $slug,
//     'route' => 'operational.job_order.show',
//     'parameter' => json_encode(['id' => $jo->id])
//   ]);
//   foreach ($userList as $un) {
//     if ($un->is_admin) {
//       NotificationUser::create([
//         'notification_id' => $n->id,
//         'user_id' => $un->id
//       ]);
//     } else {
//       if ($un->company_id==auth()->user()->company_id) {
//         NotificationUser::create([
//           'notification_id' => $n->id,
//           'user_id' => $un->id
//         ]);
//       }
//       //abaikan
//     }
//   }
// }

// end notif---------------------------------------------

        }
    }

    $total_item=1;
    if ($request->type_tarif==2) {
        $priceList=PriceList::whereRaw("service_type_id = 7 and piece_id = $request->piece_id ")->first();
        if (empty($priceList)) {
            return Response::json(['message' => 'Tarif Umum dengan Layanan ini tidak ditemukan'],500);
        }
        $price=$priceList->price_full;
        $total_price=$request->total_unit*$priceList->price_full;
// $total_price=*$price;
    } else {
        $quotationDetail=QuotationDetail::find($request->quotation_detail_id);
        $price=$quotationDetail->price_contract_full;
        $total_price=$request->total_unit*$quotationDetail->price_contract_full;
    }

    JobOrderDetail::create([
// 'piece_id' => $value['piece_id'],
        'header_id' => $jo->id,
        'quotation_id' => ($quot?$quot->header_id:null),
        'quotation_detail_id' => $request->quotation_detail_id,
        'receiver_id' => $request->receiver_id,
        'create_by' => auth()->id(),
        'is_contract' => ($quot?1:0),
        'piece_id' => $request->piece_id,
        'price' => $price,
        'total_price' => $total_price,
        'qty' => $request->total_unit,
        'imposition' => 1,
    ]);

    $ks=KpiStatus::whereRaw("service_id = $request->service_id and sort_number = 1")->first();
    $klog=KpiLog::create([
        'kpi_status_id' => $ks->id,
        'job_order_id' => $jo->id,
        'company_id' => $worr->company_id,
        'create_by' => auth()->id(),
        'date_update' => Carbon::now()
    ]);
    JobOrder::find($jo->id)->update([
        'kpi_id' => $klog->kpi_status_id,
        'total_item' => $total_item
    ]);

    DB::commit();

    return Response::json(null);
}

/**
* Display the specified resource.
*
* @param  int  $id
* @return \Illuminate\Http\Response
*/
public function show($id)
{
    $jo=JobOrder::with('trayek','moda','collectible','container_type','vehicle_type','customer','service','kpi_status','sender','receiver','service.service_type','work_order', 'invoice_detail.invoice')->where('id', $id)->first();
    $sql="
    SELECT
    manifests.id,
    manifests.code,
    contacts.name as driver,
    vehicles.nopol,
    vehicle_types.name as vname,
    CONCAT(container_types.code,' - ',container_types.name) as cname,
    IF(manifests.status=1,'Packing List',IF(manifests.status=2,'Berangkat',IF(manifests.status=3,'Sampai','Selesai'))) as status_name,
    voyage_schedules.eta,
    voyage_schedules.etd,
    containers.stuffing,
    containers.stripping,
    manifests.depart,
    manifests.arrive,
    manifests.container_id,
    CONCAT(IFNULL(vessels.name,''),' - ',IFNULL(voyage_schedules.voyage,'')) as voyage,
    CONCAT(IFNULL(container_types.code,''),' - ',IFNULL(containers.container_no,'')) as container
    FROM
    manifests
    LEFT JOIN manifest_details ON manifest_details.header_id = manifests.id
    LEFT JOIN vehicles ON manifests.vehicle_id = vehicles.id
    LEFT JOIN contacts ON contacts.id = manifests.driver_id
    LEFT JOIN vehicle_types ON vehicle_types.id = manifests.vehicle_type_id
    LEFT JOIN container_types ON container_types.id = manifests.container_type_id
    LEFT JOIN containers ON containers.id = manifests.container_id
    LEFT JOIN voyage_schedules ON voyage_schedules.id = containers.voyage_schedule_id
    LEFT JOIN vessels ON vessels.id = voyage_schedules.vessel_id
    WHERE
    manifest_details.job_order_detail_id IN ( SELECT id FROM job_order_details WHERE header_id = $id )
    GROUP BY
    manifests.id
    ";
// $jocost=JobOrderCost::where('header_id', $id)->pluck('cost_type_id');

    $data['item']=$jo;
    $data['wo_detail']=DB::table('work_order_details')
    ->leftJoin('quotation_details','work_order_details.quotation_detail_id','quotation_details.id')
    ->leftJoin('price_lists','work_order_details.price_list_id','price_lists.id')
    ->leftJoin('services as service1','quotation_details.service_id','service1.id')
    ->leftJoin('services as service2','price_lists.service_id','service2.id')
    ->whereRaw("if(work_order_details.quotation_detail_id is not null,quotation_details.service_type_id,price_lists.service_type_id) = $jo->service_type_id and work_order_details.id != $jo->work_order_detail_id and work_order_details.header_id = $jo->work_order_id")
    ->selectRaw("work_order_details.id,if(work_order_details.quotation_detail_id is not null,concat(service1.name,' - Rp.',FORMAT(ifnull(quotation_details.price_contract_full,0)+ifnull(quotation_details.price_contract_tonase,0)+ifnull(quotation_details.price_contract_volume,0)+ifnull(quotation_details.price_contract_item,0),2)),concat(service2.name,' - Rp.',format(ifnull(price_lists.price_full,0)+ifnull(price_lists.price_tonase,0)+ifnull(price_lists.price_volume,0)+ifnull(price_lists.price_item,0),2))) as name")
    ->get();
    $data['manifest']=DB::select($sql);
    $data['detail']=JobOrderDetail::with('piece')->where('header_id', $id)->get();
    $data['piece']=Piece::all();
    $data['cost_type']=CostType::with('parent')->where('is_invoice', 0)->where('company_id', $data['item']->company_id)->where('parent_id','!=',null)->get();
    $data['vendor']=Contact::whereRaw("is_vendor = 1 and vendor_status_approve = 2")->select('id','name')->get();
    $data['cost_detail']=JobOrderCost::with('cost_type','vendor')->where('header_id', $id)->get();
    $data['receipt_detail']=JobOrderReceiver::where('header_id', $id)->get();
    $data['kpi_status']=KpiStatus::where('service_id', $data['item']->service_id)->orderBy('sort_number','asc')->select('id','name')->get();
    $sql_date="
    SELECT
    kpi_statuses.name,
    kpi_statuses.status,
    kpi_logs.date_update
    FROM
    kpi_logs
    LEFT JOIN kpi_statuses ON kpi_statuses.id = kpi_logs.kpi_status_id
    WHERE
    kpi_logs.job_order_id = $id
    and kpi_statuses.status != 3
    ORDER BY
    kpi_logs.date_update desc,
    kpi_statuses.status desc";
    $tm=DB::select($sql_date);
// dd($tm);
    $durasi=0;
    foreach ($tm as $key => $value) {
// dd($tm[$key+1]??false);
        if (end($tm)==$value) {
            break;
        }
        $next=$tm[$key+1];
        $diffSecond=Carbon::parse($next->date_update)->diffInSeconds(Carbon::parse($value->date_update));
// dd($diffSecond);
        $durasi+=$diffSecond;
    }
// dd($durasi);
    $data['durasi']=convertSecs2($durasi);
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

/**
* Show the form for editing the specified resource.
*
* @param  int  $id
* @return \Illuminate\Http\Response
*/
public function edit($id)
{
    $data['item']=JobOrder::with('customer','piece','service','service.service_type','trayek','vehicle_type','container_type', 'quotation')->where('id', $id)->first();
    $data['vehicle_type']=VehicleType::select('id','name')->get();
    $data['commodity']=Commodity::select('id','name')->get();
    $data['address']=ContactAddress::whereRaw("contact_id = ".$data['item']->customer_id)->leftJoin('contacts','contacts.id','=','contact_addresses.contact_address_id')->select('contacts.id','contacts.name','contacts.address')->get();
    $data['detail_jasa']=JobOrderDetail::where('header_id', $id)->first();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

/**
* Update the specified resource in storage.
*
* @param  \Illuminate\Http\Request  $request
* @param  int  $id
* @return \Illuminate\Http\Response
*/
public function update(Request $request, $id)
{
// dd($request);
    $request->validate([
        'receiver_id' => 'required',
        'shipment_date' => 'required',
        'service_type_id' => 'required',
        'item_name' => 'required_if:service_type_id,6',
        'document_name' => 'required_if:service_type_id,6'
    ],[
        'item_name.required_if' => 'Nama Barang harus diisi',
        'document_name.required_if' => 'Nama Dokumen harus diisi',
    ]);
    DB::beginTransaction();
    $jo=JobOrder::find($id)->update([
        'sender_id' => $request->sender_id,
        'receiver_id' => $request->receiver_id,
        'commodity_id' => $request->commodity_id,
        'shipment_date' => dateDB($request->shipment_date),
        'description' => $request->description,
        'no_po_customer' => $request->wo_customer,
        'reff_no' => $request->reff_no,
        'docs_no' => $request->docs_no,
        'docs_reff_no' => $request->docs_reff_no,
        'no_bl' => $request->no_bl,
        'aju_number' => $request->aju_number,
        'vessel_name' => $request->vessel_name,
        'voyage_no' => $request->voyage_no,
    ]);
    if ($request->service_type_id==6) {
        JobOrderDetail::where('header_id', $id)->update([
            'qty' => $request->qty,
            'item_name' => $request->item_name,
            'total_price' => DB::raw('price*'.$request->qty),
        ]);
        JobOrder::find($id)->update([
            'document_name' => $request->document_name
        ]);
    }
    if ($request->service_type_id==7) {
        JobOrderDetail::where('header_id', $id)->update([
            'qty' => $request->qty,
            'total_price' => DB::raw('price*'.$request->qty),
        ]);
    }
    DB::commit();

    return Response::json(null);
}

/**
* Remove the specified resource from storage.
*
* @param  int  $id
* @return \Illuminate\Http\Response
*/
public function destroy($id)
{
// dd($id);
    DB::beginTransaction();
    $jo=JobOrder::find($id);

    WorkOrder::where('id', $jo->work_order_id)->update([
        'total_job_order' => DB::raw('total_job_order-1')
    ]);
    if ($jo->service_type_id==1) {
        foreach ($jo->detail as $value) {
            if ($value->imposition==1) {
                $qty=$value->volume;
            } elseif ($value->imposition==2) {
                $qty=$value->weight;
            } else {
                $qty=$value->qty;
            }
            WorkOrderDetail::find($jo->work_order_detail_id)->update([
                'qty_leftover' => DB::raw("qty_leftover+$qty")
            ]);
        }
    } else {
        WorkOrderDetail::find($jo->work_order_detail_id)->update([
            'qty_leftover' => DB::raw("qty_leftover+$jo->total_unit")
        ]);
    }
    $detail=JobOrderDetail::where('header_id', $id)->select('id')->get();
    foreach ($detail as $key => $value) {
        if (in_array($jo->service_type_id,[2,3,4])) {
            $md=ManifestDetail::where('job_order_detail_id', $value->id)->select('header_id')->first();
            if ($md) {
                Manifest::where('id', $md->header_id)->delete();
            }
        }
    }
    $jo->delete();
    DB::commit();
}

public function cari_wo(Request $request, $id)
{
// dd($request);
    if ($request->type_tarif==1) {
        $quot=QuotationDetail::find($request->quotation_detail_id);
        $data['wo']=WorkOrder::whereRaw("customer_id = $id AND quotation_id = $quot->header_id")->get();
    } else {
        $data['wo']=WorkOrder::where('customer_id', $id)->where('quotation_id',null)->select('id','code')->get();
    }
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

public function cari_address($id)
{
    $data['address']=ContactAddress::whereRaw("contact_id = $id")->leftJoin('contacts','contacts.id','=','contact_addresses.contact_address_id')->select('contacts.id','contacts.name','contacts.address','contact_addresses.contact_bill_id')->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

public function cari_item_kontrak($id)
{
    $data['item']=QuotationDetail::leftJoin('quotations','quotations.id','=','quotation_details.header_id')
    ->leftJoin('services','services.id','=','quotation_details.service_id')
    ->whereRaw("quotations.customer_id = $id and is_contract = 1 and date_end_contract >= date(now())")
    ->select('quotation_details.id','quotations.code','services.name as sname')->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

public function detail_kontrak($id)
{
    $v=QuotationDetail::with('header')->where('id', $id)->first();
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
}

public function add_armada(Request $request, $id)
{
// dd($request);
    $request->validate([
        'qty' => 'required|integer|min:1'
    ]);

    DB::beginTransaction();
    $jo=JobOrder::find($id);
    $price=0;
    $total_price=0;
    $wod=DB::table('work_order_details')->where('id', $jo->work_order_detail_id)->first();
    if ($wod->qty_leftover < $request->qty) {
        return Response::json(['message' => 'jumlah Armada yang anda input melebihi jumlah Armada pada Work Order!'],500);
    }
    for ($i=0; $i < $request->qty; $i++) {
        $code = new TransactionCode($jo->company_id, 'manifest');
        $code->setCode();
        $mff_code = $code->getCode();

        $m=Manifest::create([
            'company_id' => $jo->company_id,
            'transaction_type_id' => 22,
            'route_id' => $jo->route_id,
            'vehicle_type_id' => $jo->vehicle_type_id,
            'container_type_id' => $jo->container_type_id,
            'code' => $mff_code,
            'create_by' => auth()->id(),
            'date_manifest' => $jo->shipment_date,
            'is_full' => 1,
            'is_container' => ($jo->service_type_id==2?1:0)
        ]);
        $jod=JobOrderDetail::where('header_id', $jo->id)->get();
// dd($jod);
        foreach ($jod as $key => $value) {
            ManifestDetail::create([
                'header_id' => $m->id,
                'create_by' => auth()->id(),
                'update_by' => auth()->id(),
                'job_order_detail_id' => $value->id,
            ]);
        }
    }
    JobOrder::find($id)->update([
        'total_unit' => DB::raw("total_unit+".$request->qty),
        'total_price' => DB::raw("total_price+(price*$request->qty)"),
    ]);
    WorkOrderDetail::find($wod->id)->update([
        'qty' => DB::raw('qty+'.$request->qty),
        'qty_leftover' => DB::raw('qty_leftover-'.$request->qty),
    ]);
    DB::commit();

    return Response::json(null);
}

public function add_item(Request $request, $id)
{
// dd($request);
    DB::beginTransaction();
    $jo=JobOrder::find($id);
    if ($request->is_edit) {
        $jod=JobOrderDetail::find($request->detail_id);
        $request->validate([
            'piece_id' => 'required',
            'item_name' => 'required',
            'total_item' => 'required|integer|min:'.$jod->transported
        ],[
            'total_item.min' => 'Minimal barang adalah '.$jod->transported.' karena sebagian sudah terangkut'
        ]);
// dd($request);
        $jod->update([
            'qty' => $request->total_item,
            'volume' => $request->total_volume,
            'weight' => $request->total_tonase,
            'item_name' => $request->item_name,
            'no_reff' => $request->reff_no,
            'no_manifest' => $request->manifest_no,
            'piece_id' => $request->piece_id,
            'description' => $request->description,
            'leftover' => ($jod->leftover+$request->total_item-$jod->qty)
        ]);


        if ($jo->service_type_id==1) {
            if (empty($jo->quotation_id)) {
                $priceList=PriceList::whereRaw("service_type_id = 1 and moda_id = $jo->moda_id and route_id = $jo->route_id and vehicle_type_id = $jo->vehicle_type_id")->first();
                if (empty($priceList)) {
                    return Response::json(['message' => 'Tarif Umum dengan trayek dan armada ini tidak ditemukan'],500);
                }
                if ($request->imposition==1) {
                    $price=$priceList->price_volume;
                    $total_price=$price*$request->total_volume;
                } elseif ($request->imposition==2) {
                    $price=$priceList->price_tonase;
                    $total_price=$price*$request->total_tonase;
                } else {
                    $price=$priceList->price_item;
                    $total_price=$price*$request->total_item;
                }
// $total_price=*$price;
            } else {
                $quotationDetail=QuotationDetail::find($jo->quotation_detail_id);
                if ($request->imposition==1) {
                    $price=$quotationDetail->price_contract_volume;
                    $total_price=$price*$request->total_volume;
                } elseif ($request->imposition==2) {
                    $price=$quotationDetail->price_contract_tonase;
                    $total_price=$price*$request->total_tonase;
                } else {
                    $price=$quotationDetail->price_contract_item;
                    $total_price=$price*$request->total_item;
                }
            }
            JobOrderDetail::find($request->detail_id)->update([
                'price' => $price,
                'total_price' => $total_price,
            ]);
        }
        DB::commit();

        return Response::json(null,200);
    }
    if ($jo->service_type_id==1) {
        $request->validate([
            'imposition' => 'required',
            'piece_id' => 'required',
            'total_item' => 'required_if:imposition,3',
            'total_volume' => 'required_if:imposition,1',
            'total_tonase' => 'required_if:imposition,2',
            'item_name' => 'required',
        ]);
//tipe layanan retail
        if (empty($jo->quotation_id)) {
            $priceList=PriceList::whereRaw("service_type_id = 1 and moda_id = $jo->moda_id and route_id = $jo->route_id and vehicle_type_id = $jo->vehicle_type_id")->first();
            if (empty($priceList)) {
                return Response::json(['message' => 'Tarif Umum dengan trayek dan armada ini tidak ditemukan'],500);
            }
            if ($request->imposition==1) {
                $price=$priceList->price_volume;
                $total_price=$price*$request->total_volume;
            } elseif ($request->imposition==2) {
                $price=$priceList->price_tonase;
                $total_price=$price*$request->total_tonase;
            } else {
                $price=$priceList->price_item;
                $total_price=$price*$request->total_item;
            }
// $total_price=*$price;
        } else {
            $quotationDetail=QuotationDetail::find($jo->quotation_detail_id);
            if ($request->imposition==1) {
                $price=$quotationDetail->price_contract_volume;
                $total_price=$price*$request->total_volume;
            } elseif ($request->imposition==2) {
                $price=$quotationDetail->price_contract_tonase;
                $total_price=$price*$request->total_tonase;
            } else {
                $price=$quotationDetail->price_contract_item;
                $total_price=$price*$request->total_item;
            }
        }
    } else {
//tipe layanan ftl
        $request->validate([
            'piece_id' => 'required',
            'total_item' => 'required',
            'total_volume' => 'required',
            'total_tonase' => 'required',
            'item_name' => 'required',
            'reff_no' => 'required',
            'manifest_no' => 'required',
        ]);

        if (empty($jo->quotation_id)) {
            if ($jo->service_type_id==2) {
                $wrAdd=" and service_type_id = 2 and container_type_id = $jo->container_type_id";
            } else {
                $wrAdd=" and service_type_id = 3 and vehicle_type_id = $jo->vehicle_type_id";
            }
            $priceList=PriceList::whereRaw("route_id = $jo->route_id $wrAdd")->first();
            if (empty($priceList)) {
                return Response::json(['message' => 'Mohon Maaf, Tarif Umum dengan trayek dan armada ini tidak ditemukan. Silahkan Setting di master'],500);
            }
            $price=$priceList->price_full;
            $total_price=$jo->total_unit*$priceList->price_full;
        } else {
            $quotationDetail=QuotationDetail::find($jo->quotation_detail_id);
            $price=$quotationDetail->price_contract_full;
            $total_price=$jo->total_unit*$quotationDetail->price_contract_full;
        }
    }

    JobOrderDetail::create([
// 'piece_id' => $value['piece_id'],
        'header_id' => $jo->id,
        'quotation_id' => $jo->quotation_id,
        'quotation_detail_id' => $jo->quotation_detail_id,
        'commodity_id' => $jo->commodity_id,
        'sender_id' => $jo->sender_id,
        'receiver_id' => $jo->receiver_id,
        'create_by' => auth()->id(),
        'is_contract' => ($jo->quotation_id?1:0),
        'piece_id' => $request->piece_id,
        'price' => (!in_array($jo->service_type_id,[2,3])?$price:0),
        'total_price' => (!in_array($jo->service_type_id,[2,3])?$total_price:0),
        'qty' => $request->total_item,
        'volume' => $request->total_volume,
        'weight' => $request->total_tonase,
        'item_name' => $request->item_name,
        'barcode' => $request->barcode,
        'imposition' => $request->imposition,
        'description' => $request->description,
        'leftover' => $request->total_item,
    ]);

    $jo->update([
        'total_item' => DB::raw("total_item+1")
    ]);
    DB::commit();

    return Response::json(null);
}

public function add_item_warehouse(Request $request, $id)
{
// dd($request);
    DB::beginTransaction();
    $jo=JobOrder::find($id);
    if($jo == null) {
        return Response::json(['message' => 'Transaksi tidak ditemukan'], 422);
    }
    else {
        $jo_service = $jo->with('service:id,name,is_warehouse');
        if($jo->service->is_warehouse != 1) {

            return Response::json(['message' => 'Transaksi tidak ditemukan'], 422);
        }
    }
    $item_piece = DB::table('pieces')->where('name', 'Item')->first();

    $price = 0;
    $total_price = 0;
    $subtotal_price = 0;
    if($jo->is_handling == 1 || $jo->is_stuffing == 1) {
        $work_order_detail = WorkOrderDetail::find($jo->work_order_detail_id);
// Tarif umum atau kontrak
        if($work_order_detail->price_list_id != null) {
// Jika dari tarif umum
            $price_list = PriceList::find($work_order_detail->price_list_id);
            $price_borongan = $price_list->price_full;
            $price_volume = $price_list->price_volume;
            $price_tonase = $price_list->price_tonase;
            $price_item = $price_list->price_item;
        }
        else {
            $price_borongan = 0;
            $price_volume = 0;
            $price_tonase = 0;
            $price_item = 0;

        }

        if($request->imposition == 1) {
// Tarif berdasarkan volume
            $price = $price_volume;
            $subtotal_price = $request->total_volume * $price_volume;
        }
        else if($request->imposition == 2) {
// Tarif berdasarkan berat / tonase
            $price = $price_tonase;
            $subtotal_price = $request->total_tonase * $price_tonase;
        }
        else if($request->imposition == 3) {
// Tarif berdasarkan item
            $price = $price_item;
            $subtotal_price = $request->total_item * $price_item;
        }
        else {
// Tarif borongan
            $price = $price_borongan;
            $subtotal_price = $price_borongan;
        }
    }
    else if($jo->is_warehouserent == 1) {
        $work_order_detail = WorkOrderDetail::find($jo->work_order_detail_id);

// Tarif umum atau kontrak
        if($work_order_detail->price_list_id != null) {
// Jika dari tarif umum
            $price_list = PriceList::find($work_order_detail->price_list_id);
            $price_borongan = $price_list->price_borongan;
            $price_volume = $price_list->price_volume;
            $price_tonase = $price_list->price_tonase;
            $price_item = $price_list->price_item;
            $price_harian = $price_list->price_harian;
        }
        else {
            $price_borongan = 0;
            $price_volume = 0;
            $price_tonase = 0;
            $price_item = 0;
            $price_harian = 0;
        }

        $subtotal_price = $request->total_item * $price_harian;

        if($request->imposition == 1) {
            $price = $price_harian * $price_volume;
            $subtotal_price = $subtotal_price * $price_volume;
        }
        else if($request->imposition == 2) {
            $price = $price_harian * $price_tonase;
            $subtotal_price = $subtotal_price * $price_tonase;
        }
        else if($request->imposition == 3) {
            $price = $price_harian * $price_item;
            $subtotal_price = $subtotal_price * $price_item;
        }
        else if($request->imposition == 4) {
            $price = $price_harian * $price_borongan;
            $subtotal_price = $subtotal_price * $price_borongan / $value['total_item'];
        }
    }

    if($jo->is_handling == 1) {
        $h = Handling::where('job_order_id', $id)->first();
        $handling_storage = DB::table('racks')->join('storage_types', 'storage_type_id', 'storage_types.id')->where('is_handling_area', 1)->where('warehouse_id', $h->warehouse_id)->select('racks.id');


        if($handling_storage->count() == 0) {
            $r = Rack::create([
                'warehouse_id' => $h->warehouse_id,
                'barcode' => '-',
                'code' => 'Handling Area',
                'storage_type_id' => StorageType::where('is_handling_area', 1)->first()->id,
                'capacity_volume' => 100000,
                'capacity_tonase' => 100000
            ]);

            $request->rack_id = $r->id;
        }
        else {
            $request->rack_id = $handling_storage->first()->id;
        }

    }
    else if($jo->is_stuffing == 1 AND $request->storage_type != 'RACK') {
        $s = Stuffing::where('job_order_id', $id)->first();
        $picking_storage = Rack::leftJoin('storage_types', 'storage_type_id', 'storage_types.id')->where('is_picking_area', 1)->where('warehouse_id', $s->warehouse_id)->select('racks.id');;
        if($picking_storage->count() == 0) {
            $r = Rack::create([
                'warehouse_id' => $h->warehouse_id,
                'barcode' => '-',
                'code' => 'Picking Area',
                'storage_type_id' => StorageType::where('is_picking_area', 1)->first()->id,
                'capacity_volume' => 100000,
                'capacity_tonase' => 100000
            ]);

            $request->rack_id = $r->id;
        }
        $request->rack_id = $picking_storage->first()->id;

    }

    if(isset($request->item_id)) {
        if(Item::find($request->item_id) == null) {
            return Response::json(['message' => 'Item tidak ditemukan'], 422);
        }
    }

    if(isset($request->rack_id)) {
        if(!isset($request->storage_type)) {
            if(Rack::find($request->rack_id) == null) {
                return Response::json(['message' => 'Rak penyimpanan tidak ditemukan'], 422);
            }
        }
        if($request->storage_type == 'RACK') {

            if(Rack::find($request->rack_id) == null) {
                return Response::json(['message' => 'Rak penyimpanan tidak ditemukan'], 422);
            }
        }
    }

    $ws = WarehouseStockDetail::whereRackId($request->rack_id)->whereNoSuratJalan($request->no_surat_jalan)->whereItemId($request->item_id)->first();
    if($ws == null) {
        $i = Item::find($request->item_id);
        return Response::json([
            "message" => 'Stok ' . $i->name . ' tidak mencukupi',
            'item' => [
                'name' => $i->name,
                'qty_stock' => 0,
                'qty_dikeluarkan' => $request->total_item
            ]
        ], 422);
    } else {
        if($ws->qty < $request->total_item) {
            $i = Item::find($request->item_id);
            return Response::json([
                "message" => 'Stok ' . $i->name . ' tidak mencukupi',
                'item' => [
                    'name' => $i->name,
                    'qty_stock' => $ws->qty,
                    'qty_dikeluarkan' => $request->total_item
                ]
            ], 422);
        }
    }


    if($request->is_edit) {
        JobOrderDetail::find($request->detail_id)->update([
// 'piece_id' => $value['piece_id'],
            'qty' => $request->total_item,
            'long' => $request->long,
            'wide' => $request->wide,
            'price' => $price,
            'total_price' => $subtotal_price,
            'high' => $request->high,
            'volume' => $request->total_volume,
            'weight' => $request->total_tonase,
            'no_surat_jalan' => $request->no_surat_jalan,
            'imposition' => $request->imposition,
            'rack_id' => $request->rack_id,
            'item_id' => $request->item_id,
            'item_name' => $request->item_name,
// 'barcode' => $request->barcode,
            'description' => $request->description,
        ]);
    }
    else {
        JobOrderDetail::create([
// 'piece_id' => $value['piece_id'],
            'header_id' => $jo->id,
            'create_by' => auth()->id(),
            'piece_id' => $item_piece->id,
            'price' => $price,
            'total_price' => $subtotal_price,
            'qty' => $request->total_item,
            'long' => $request->long,
            'wide' => $request->wide,
            'high' => $request->high,
            'volume' => $request->total_volume,
            'weight' => $request->total_tonase,
            'no_surat_jalan' => $request->no_surat_jalan,
            'imposition' => $request->imposition,
            'rack_id' => $request->rack_id,
            'item_id' => $request->item_id,
            'item_name' => $request->item_name,
// 'barcode' => $request->barcode,
            'description' => $request->description,
        ]);
    }

    $jo->update([
        'total_item' => DB::raw("total_item+1")
    ]);
    DB::commit();
    if(isset($request->is_edit)) {
        return Response::json(['message' => 'Item berhasil di-update'], 200);
    }

    return Response::json(['message' => 'Item berhasil ditambahkan'], 200);
}

public function edit_cost($id)
{
    $item= JobOrderCost::find($id);
    return Response::json($item,200,[],JSON_NUMERIC_CHECK);
}

public function add_cost(Request $request, $id)
{
// dd($request);
    $request->validate([
        'cost_type' => 'required',
        'vendor_id' => 'required',
        'qty' => 'required|integer|min:1',
        'total_price' => 'required|integer|min:1',
        'price' => 'required|integer|min:1',
    ]);
    DB::beginTransaction();
    $jo=JobOrder::find($id);
    if($jo == null) {
        return Response::json(['message' => 'Transaksi tidak ditemukan'], 422);
    }
    $ctt=CostType::find($request->cost_type);
    $slug=str_random(6);
    if ($request->is_edit) {
        JobOrderCost::find($request->id)->update([
            'cost_type_id' => $request->cost_type,
            'vendor_id' => $request->vendor_id,
            'qty' => $request->qty,
            'price' => $request->price,
            'total_price' => $request->total_price,
            'description' => $request->description,
            'type' => $request->type,
            'is_edit' => 1
        ]);
        $jc=JobOrderCost::find($request->id);
        $slug=$jc->slug??$slug;
    } else {
        $jc=JobOrderCost::create([
            'header_id' => $id,
            'cost_type_id' => $request->cost_type,
            'transaction_type_id' => 21,
            'vendor_id' => $request->vendor_id,
            'qty' => $request->qty,
            'price' => $request->price,
            'total_price' => $request->total_price,
            'description' => $request->description,
            'create_by' => auth()->id(),
            'status' => 1,
            'type' => $request->type,
            'slug' => $slug,
            'is_edit' => 1
        ]);
    }
//notif----------------------------------------
    if ($jc->total_price < 50000000) {
        $userList=DB::table('notification_type_users')
        ->leftJoin('users','users.id','=','notification_type_users.user_id')
        ->whereRaw("notification_type_users.notification_type_id = 6")
        ->select('users.id','users.is_admin','users.company_id')->get();
        $n=Notification::create([
            'notification_type_id' => 6,
            'name' => 'Ada Biaya Job Order Baru yang memerlukan persetujuan Supervisi!',
            'description' => 'No. JO '.$jo->code.' pada biaya '.$ctt->name,
            'slug' => $slug,
            'route' => 'operational.job_order.show',
            'parameter' => json_encode(['id' => $jo->id])
        ]);
        foreach ($userList as $un) {
            if ($un->is_admin) {
                NotificationUser::create([
                    'notification_id' => $n->id,
                    'user_id' => $un->id
                ]);
            } else {
                if ($un->company_id==auth()->user()->company_id) {
                    NotificationUser::create([
                        'notification_id' => $n->id,
                        'user_id' => $un->id
                    ]);
                }
//abaikan
            }
        }
    } elseif ($jc->total_price < 100000000) {
        $userList=DB::table('notification_type_users')
        ->leftJoin('users','users.id','=','notification_type_users.user_id')
        ->whereRaw("notification_type_users.notification_type_id = 7")
        ->select('users.id','users.is_admin','users.company_id')->get();
        $n=Notification::create([
            'notification_type_id' => 7,
            'name' => 'Ada Biaya Job Order Baru yang memerlukan persetujuan Manajer!',
            'description' => 'No. JO '.$jo->code.' pada biaya '.$ctt->name,
            'slug' => $slug,
            'route' => 'operational.job_order.show',
            'parameter' => json_encode(['id' => $jo->id])
        ]);
        foreach ($userList as $un) {
            if ($un->is_admin) {
                NotificationUser::create([
                    'notification_id' => $n->id,
                    'user_id' => $un->id
                ]);
            } else {
                if ($un->company_id==auth()->user()->company_id) {
                    NotificationUser::create([
                        'notification_id' => $n->id,
                        'user_id' => $un->id
                    ]);
                }
//abaikan
            }
        }
    } else {
        $userList=DB::table('notification_type_users')
        ->leftJoin('users','users.id','=','notification_type_users.user_id')
        ->whereRaw("notification_type_users.notification_type_id = 8")
        ->select('users.id','users.is_admin','users.company_id')->get();
        $n=Notification::create([
            'notification_type_id' => 8,
            'name' => 'Ada Biaya Job Order Baru yang memerlukan persetujuan Direksi!',
            'description' => 'No. JO '.$jo->code.' pada biaya '.$ctt->name,
            'slug' => $slug,
            'route' => 'operational.job_order.show',
            'parameter' => json_encode(['id' => $jo->id])
        ]);
        foreach ($userList as $un) {
            if ($un->is_admin) {
                NotificationUser::create([
                    'notification_id' => $n->id,
                    'user_id' => $un->id
                ]);
            } else {
                if ($un->company_id==auth()->user()->company_id) {
                    NotificationUser::create([
                        'notification_id' => $n->id,
                        'user_id' => $un->id
                    ]);
                }
//abaikan
            }
        }
    }
//end notif-------------------------------------
    DB::commit();
    if(isset($request->is_edit)) {
        if($request->is_edit == true) {
            return Response::json(['message' => 'Biaya berhasil di-update'], 200);
        }
    }
    return Response::json(['message' => 'Biaya berhasil ditambahkan'], 200);
}
public function add_receipt(Request $request, $id)
{
// dd($request);
    $request->validate([
        'receiver' => 'required',
        'date_receive' => 'required',
    ]);
    DB::beginTransaction();
    $jo=JobOrder::find($id);
    JobOrderReceiver::create([
        'header_id' => $id,
        'date_receive' => dateDB($request->date_receive),
        'receiver' => $request->receiver,
        'telephone' => $request->telephone,
        'description' => $request->description,
        'create_by' => auth()->id(),
    ]);
    DB::commit();

    return Response::json(null);
}

public function update_detail(Request $request, $id)
{
// dd($request);
// $request->validate([
//   'qty' => 'required',
// ]);
    DB::beginTransaction();
    JobOrderDetail::find($id)->update([
        'qty' => $request->qty
    ]);
    DB::commit();

    return Response::json(null);
}
public function add_status(Request $request, $id)
{
// dd($request);
// $request->validate([
//   'update_date' => 'required',
//   'update_time' => 'required',
//   'kpi_status_id' => 'required',
// ]);
    DB::beginTransaction();
    $jo=JobOrder::find($id);
    if($jo == null) {
        return Response::json(['message' => 'Transaksi tidak ditemukan'], 422);
    }

    $ks = KpiStatus::whereId($request->kpi_status)->whereServiceId($jo->service_id);
    if($ks == null) {
        $service_name = Service::find($jo->id)->name;
        return Response::json(['message' => 'KPI Status untuk layanan ' . $service_name . ' tidak ditemukan'], 422);
    }

    KpiLog::create([
        'job_order_id' => $id,
        'kpi_status_id' => $request->kpi_status_id,
        'date_update' => createTimestamp($request->update_date,$request->update_time),
        'company_id' => $jo->company_id,
        'description' => $request->description,
        'create_by' => auth()->id(),
    ]);
    $jo->update([
        'kpi_id' => $request->kpi_status_id
    ]);

    $jo = JobOrder::find($id);
    $service = Service::find($jo->service_id);
    $ks = KpiStatus::find($request->kpi_status_id);
    if($service->is_warehouse == 1) {
        if($ks->is_done == 1) {
            if($service->name == 'Handling'){
                $handling = DB::table('handlings')->where('job_order_id', $jo->id);
                $handling->update(['status' => 1]);
            }
            else if($service->name == 'Packaging'){
                $packaging = DB::table('packagings')->where('job_order_id', $jo->id);
                $packaging->update(['status' => 1]);
            }
            else if($service->name == 'Warehouse Rent'){
                $warehouserent = DB::table('warehouserents')->where('job_order_id', $jo->id);
                $warehouserent->update(['status' => 1]);
            }
            else if($service->name == 'Stuffing'){
                $stuffing = DB::table('stuffings')->where('job_order_id', $jo->id);
                $stuffing->update(['status' => 1]);
            }
        }
    }
    DB::commit();

    return Response::json(['message' => 'Status berhasil di-update'], 200);
}

public function show_document($id)
{
    $data['detail']=JobOrderDocument::where('header_id', $id)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}
public function show_status($id)
{
    $jo=JobOrder::find($id);
    if($jo == null) {
        return Response::json(['message' => 'Transaksi tidak ditemukan'], 422);
    }
// $data['kpi_status']=KpiStatus::where('service_id', $jo->service_id)->orderBy('sort_number','asc')->select('id','name')->get();
    $data['detail']=KpiLog::with('kpi_status:id,name','creates:id,name')->where('job_order_id', $id)->selectRaw('id, kpi_status_id, create_by, description, DATE_FORMAT(date_update, "%d-%m-%Y %H:%i:%s") AS date_update')->orderBy('created_at','desc')->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

public function update_status(Request $request)
{
// $request->validate([
//   'id' => 'required',
//   'update_date' => 'required',
//   'update_time' => 'required',
//   'kpi_status_id' => 'required',
// ]);
    DB::beginTransaction();
    $kl=KpiLog::with('kpi_status')->whereId($request->id)->first();
    $jo = JobOrder::find($kl->job_order_id);
    if($kl == null) {
        return Response::json(['message' => 'Detail proses tidak ditemukan'], 422);
    }
    else {
        if($jo == null) {

        return Response::json(['message' => 'Transaksi tidak ditemukan'], 422);
        }
    }
    $ks = KpiStatus::whereId($request->kpi_status)->whereServiceId($jo->service_id);
    if($ks == null) {
        $service_name = Service::find($kl->job_order_id)->name;
        return Response::json(['message' => 'KPI Status untuk layanan ' . $service_name . ' tidak ditemukan'], 422);
    }
    $list=DB::table('kpi_logs')->where('job_order_id', $kl->job_order_id)->orderBy('created_at', 'desc')->select('created_at')->first();
// dd($kl);
    if ($kl->created_at==$list->created_at) {
        JobOrder::find($kl->job_order_id)->update([
            'kpi_id' => $request->kpi_status_id
        ]);
    }
    $kl->update([
        'kpi_status_id' => $request->kpi_status_id,
        'date_update' => createTimestamp($request->update_date,$request->update_time),
        'description' => $request->description
    ]);

    $kl->kpi_status = KpiStatus::find($request->kpi_status_id);

    $jo = JobOrder::find($kl->job_order_id);
    $service = Service::find($jo->service_id);
    if($service->is_warehouse == 1) {
        if($kl->kpi_status->is_done == 1) {
            if($service->name == 'Handling'){
                $handling = DB::table('handlings')->where('job_order_id', $jo->id);
                $handling->update(['status' => 1]);
            }
            else if($service->name == 'Packaging'){
                $packaging = DB::table('packagings')->where('job_order_id', $jo->id);
                $packaging->update(['status' => 1]);
            }
            else if($service->name == 'Warehouse Rent'){
                $warehouserent = DB::table('warehouserents')->where('job_order_id', $jo->id);
                $warehouserent->update(['status' => 1]);
            }
            else if($service->name == 'Stuffing'){
                $stuffing = DB::table('stuffings')->where('job_order_id', $jo->id);
                $stuffing->update(['status' => 1]);
            }
        }
    }
    DB::commit();
    return Response::json(['message' => 'Proses berhasil di-update'], 200);
}

public function delete_status($id)
{
    DB::beginTransaction();
    $k = KpiLog::where('id', $id)->first();
    if($k == null) {
        return Response::json(['message' => 'Riwayat proses tidak ditemukan!'],500);
    }
    $j = KpiLog::where('job_order_id', $k->job_order_id)->count('id');
    if($j == 1) {
        return Response::json(['message' => 'Harus menyisakan 1 riwayat proses!'],500);
    }
    $k = KpiLog::find($id);
    $k->delete();
    DB::commit();
    return Response::json(['message' => 'Proses berhasil dihapus'], 200);

}

public function upload_file(Request $request, $id)
{
// dd($request);
    ini_set('max_execution_time', 120);
// dd($request);
// $request->validate([
//   'file' => 'required',
//   'name' => 'required'
// ]);
    $file=$request->file('file');
    $filename="JOBORDER_".$id."_".date('Ymd_His').'_'.str_random(6).'.'.$file->getClientOriginalExtension();
    DB::beginTransaction();
    JobOrderDocument::create([
        'header_id' => $id,
        'name' => $request->name,
        'description' => $request->description,
        'file_name' => 'files/'.$filename,
        'create_by' => auth()->id(),
        'upload_date' => date('Y-m-d'),
        'extension' => $file->getClientOriginalExtension(),
        'is_customer_view' => $request->is_customer_view??0
    ]);
    $file->move(public_path('files'), $filename);
    DB::commit();

    return Response::json(null);
}

public function delete_file($id)
{
    DB::beginTransaction();
    $fl=JobOrderDocument::find($id);
// Storage::delete($fl->filename);
    $s=File::delete(public_path().'/'.$fl->file_name);
// dd($s);
    if ($s) {
        $fl->delete();
    }
    DB::commit();

    return Response::json(null);
}

public function ajukan_atasan(Request $request)
{
    if(JobOrderCost::find($request->id) == null) {
        return Response::json(['message' => 'Detail biaya tidak ditemukan'], 422);
    }
    DB::beginTransaction();
    JobOrderCost::find($request->id)->update([
        'status' => 7
    ]);
    DB::commit();

    return Response::json(['message' => 'Biaya berhasil diajukan'],200,[],JSON_NUMERIC_CHECK);
}

public function approve_atasan(Request $request)
{
    DB::beginTransaction();
    JobOrderCost::find($request->id)->update([
        'status' => 8,
        'approve_by' => auth()->id()
    ]);

    DB::commit();
    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
}

public function reject_atasan(Request $request)
{
    DB::beginTransaction();
    JobOrderCost::find($request->id)->update([
        'status' => 4
    ]);

    DB::commit();
    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
}

public function store_submission($id)
{
    $jobOrderCost=JobOrderCost::find($id);
    $jo=JobOrder::find($jobOrderCost->header_id);
// $h=Manifest::find($mc->header_id);
    DB::beginTransaction();
    $costType=DB::table('cost_types')->where('id', $jobOrderCost->cost_type_id)->first();
// $ven = Contact::find($jobOrderCost->vendor_id);

// dd($ct);
    $jurnal = Journal::create([
        'company_id' => $jo->company_id,
        'type_transaction_id' => 50,
        'date_transaction' => $jo->shipment_date,
        'created_by' => auth()->id(),
        'code' => $jo->code,
        'status' => 2,
        'description' => "Biaya Job Order - $costType->name - $jo->code",
        'debet' => 0,
        'credit' => 0,
    ]);
    $mc->update([
        'status' => 2,
    ]);

    DB::commit();

    return Response::json(null);
}

public function cari_price_list($id)
{
    $data=PriceList::find($id);
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

public function set_voyage($id)
{
    $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address','company_id')->get();
    $data['manifest']=Manifest::find($id);
    $data['voyage_schedule']=VoyageSchedule::with('vessel')->get();
    $data['port']=Port::all();
    $data['vessel']=Vessel::all();
    $data['commodity']=Commodity::all();
    $data['container_type']=ContainerType::all();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

public function cari_container($voy)
{
    $data=Container::where('voyage_schedule_id', $voy)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
}

function store_voyage_vessel(Request $request, $man)
{
// return Response::json($request,500);
// $request->validate([
//   'vessel_id' => 'required',
//   'voyage' => 'required',
//   'pol_id' => 'required',
//   'pod_id' => 'required',
//   'etd_date' => 'required',
//   'eta_date' => 'required',
//   'etd_time' => 'required',
//   'eta_time' => 'required',
//   // 'voyage_schedule_id' => 'required',
//   'container_no' => 'required',
//   'container_type_id' => 'required',
//   'commodity_id' => 'required',
//   'commodity' => 'required',
// ]);

    $mn=Manifest::find($man);
    DB::beginTransaction();
    if (!$request->voyage_schedule_id) {
        $vss=VoyageSchedule::create([
            'vessel_id' => $request->vessel_id,
            'pol_id' => $request->pol_id,
            'pod_id' => $request->pod_id,
            'voyage' => $request->voyage,
            'total_container' => 0,
            'etd' => createTimestamp($request->etd_date,$request->etd_time),
            'eta' => createTimestamp($request->eta_date,$request->eta_time),
            'create_by' => auth()->id(),
        ]);

        $vs=VoyageSchedule::find($vss->id);

        if (isset($request->stripping_date) && isset($request->stripping_time)) {
            $stripping=createTimestamp($request->stripping_date,$request->stripping_time);
        }
        if (isset($request->stuffing_date) && isset($request->stuffing_time)) {
            $stuffing=createTimestamp($request->stuffing_date,$request->stuffing_time);
        }

        $c=Container::create([
            'container_type_id' => $request->container_type_id,
            'vessel_id' => $vs->vessel_id,
            'company_id' => $mn->company_id,
            'commodity_id' => $request->commodity_id,
            'voyage_schedule_id' => $vs->id,
            'container_no' => $request->container_no,
            'booking_date' => dateDB($request->booking_date),
            'booking_number' => $request->booking_number,
            'departure' => $vs->departure,
            'arrival' => $vs->arrival,
            'seal_no' => $request->seal_no,
            'is_fcl' => 1,
            'commodity' => $request->commodity,
            'create_by' => auth()->id(),
            'stripping' => $stripping??null,
            'stuffing' => $stuffing??null,
        ]);
        $mn->update([
            'container_id' => $c->id,
        ]);

    } elseif (!$request->container_id) {
        $vs=VoyageSchedule::find($request->voyage_schedule_id);

        if (isset($request->stripping_date) && isset($request->stripping_time)) {
            $stripping=createTimestamp($request->stripping_date,$request->stripping_time);
        }
        if (isset($request->stuffing_date) && isset($request->stuffing_time)) {
            $stuffing=createTimestamp($request->stuffing_date,$request->stuffing_time);
        }

        $c=Container::create([
            'container_type_id' => $request->container_type_id,
            'vessel_id' => $vs->vessel_id,
            'company_id' => $mn->company_id,
            'commodity_id' => $request->commodity_id,
            'voyage_schedule_id' => $vs->id,
            'container_no' => $request->container_no,
            'booking_date' => dateDB($request->booking_date),
            'booking_number' => $request->booking_number,
            'departure' => $vs->departure,
            'arrival' => $vs->arrival,
            'seal_no' => $request->seal_no,
            'is_fcl' => 1,
            'commodity' => $request->commodity,
            'create_by' => auth()->id(),
            'stripping' => $stripping??null,
            'stuffing' => $stuffing??null,
        ]);
        $mn->update([
            'container_id' => $c->id,
        ]);
    } else {
        $mn->update([
            'container_id' => $request->container_id,
        ]);
    }
    DB::commit();

    return Response::json(null);
}

public function store_revision(Request $request, $id)
{
    $request->validate([
        'qty' => 'required|min:1',
        'price' => 'required|min:1',
        'total_price' => 'required|min:1'
    ]);
    DB::beginTransaction();
    JobOrderCost::find($id)->update([
        'before_revision_cost' => $request->before_revision_cost,
        'total_price' => $request->total_price,
        'qty' => $request->qty,
        'price' => $request->price,
        'total_price' => $request->total_price,
        'vendor_id' => $request->vendor_id,
        'description' => $request->description,
        'status' => 6,
    ]);
    SubmissionCost::whereRaw("relation_cost_id = $id and type_submission = 1")->update([
        'status' => 5,
        'revision_date' => Carbon::now()
    ]);
    DB::commit();

    return Response::json(null);
}

public function delete_item($id)
{
    DB::beginTransaction();
    if(JobOrderDetail::find($id) == null) {
        return Response::json(['message' => 'Detail barang tidak ditemukan'], 422);
    }
    JobOrderDetail::find($id)->delete();
    DB::commit();

    return Response::json(['message' => 'Barang berhasil dihapus']);
}

public function delete_cost($id)
{
    DB::beginTransaction();
    if(JobOrderCost::find($id) == null) {
        return Response::json(['message' => 'Detail biaya tidak ditemukan'], 422);
    }
    JobOrderCost::find($id)->delete();
    DB::commit();
    return Response::json(['message' => 'Detail biaya tidak ditemukan'], 200);
}
public function send_notification(Request $request)
{
    DB::beginTransaction();
    $userList=DB::table('notification_type_users')
    ->leftJoin('users','users.id','=','notification_type_users.user_id')
    ->whereRaw("notification_type_users.notification_type_id = 1")
    ->select('users.id','users.is_admin','users.company_id')->get();
    $n=Notification::create([
        'notification_type_id' => 1,
        'name' => 'Ada Pesan baru dari Operasional!',
        'description' => $request->description.' - <b>'.auth()->user()->name.' - '.auth()->user()->company->name.'</b>',
// 'slug' => $slug,
        'route' => 'marketing.operational_notification',
        'parameter' => null
    ]);
    foreach ($userList as $un) {
        if ($un->is_admin) {
            NotificationUser::create([
                'notification_id' => $n->id,
                'user_id' => $un->id
            ]);
        } else {
            if ($un->company_id==auth()->user()->company_id) {
                NotificationUser::create([
                    'notification_id' => $n->id,
                    'user_id' => $un->id
                ]);
            }
//abaikan
        }
    }

    DB::commit();
    return Response::json(['message' => 'Pesan berhasil dikirim ke marketing'],200);
}

public function store_archive(Request $request)
{
// dd($request->detail);
    DB::beginTransaction();
    foreach ($request->detail as $key => $value) {
        if ($value['value']==1) {
            if(JobOrder::find($key) == null) {
                return Response::json(['message' => 'Transaksi tidak ditemukan'], 422);
            } else {
                if(JobOrder::find($key)->is_operational_done == 1) {
                    $code = JobOrder::find($key)->code;
                    return Response::json(['message' => 'Transaksi ' . $code . ' tidak dapat dipindahkan lagi, karena sudah berada si arsip job order'], 422);
                }
            }
            JobOrder::find($key)->update([
                'is_operational_done' => 1
            ]);
        }
    }
    DB::commit();

    return Response::json(['message' => 'Transaksi berhasil dipindahkan ke arsip job order'], 422);
}

public function delete_armada($id)
{
    DB::beginTransaction();
    $m=Manifest::find($id);
    $detail=ManifestDetail::where('header_id', $id)->get();
    $jod=ManifestDetail::where('manifest_details.header_id', $id)->leftJoin('job_order_details','job_order_details.id','=','manifest_details.job_order_detail_id')->groupBy('job_order_details.header_id')->pluck('job_order_details.header_id');
// dd($jod);
// return Response::json($jod,500);
    foreach ($detail as $key => $value) {
        JobOrderDetail::where('id',$value->job_order_detail_id)->update([
            'leftover' => DB::raw("leftover+$value->transported"),
            'transported' => DB::raw("transported-$value->transported"),
        ]);
    }
    foreach ($jod as $key => $value) {
// dd($value);
        $jo=JobOrder::find($value);
        $satuan=$jo->price;
// dd($jo);
        WorkOrderDetail::where('id', $jo->work_order_detail_id)->update([
            'qty_leftover' => DB::raw('qty_leftover+1')
        ]);
        $jo->update([
            'total_price' => $jo->price*($jo->total_unit-1),
            'total_unit' => DB::raw('total_unit-1')
        ]);
    }
    $m->delete();
    DB::commit();

    return Response::json(null);
}

public function change_service(Request $request, $id)
{
// dd($request);
    DB::beginTransaction();
    $jo=JobOrder::find($id);
    $wod=WorkOrderDetail::find($request->work_order_detail_id);
    $old_wod=WorkOrderDetail::find($request->old_work_order_detail_id);
    $detail=JobOrderDetail::where('header_id', $id);
    if (in_array($jo->service_type_id,[6,7])) {
//jasa kepabeanan & jasa lainnya
        $totalQty=0;
        foreach ($detail->get() as $value) {
            $totalQty+=$value->qty;
        }
        if ($totalQty>$wod->qty_leftover) {
            return Response::json(['message' => 'Qty pada Work Order tidak mencukupi, silahkan menambahkan Qty pada item Work Order!'],500);
        }
        if ($wod->quotation_detail_id) {
            $tarif=$wod->quotation_detail->price_contract_full;
            $service=$wod->quotation_detail->service_id;
        } else {
            $tarif=$wod->price_list->price_full;
            $service=$wod->price_list->service_id;
        }
        $detail->update([
            'price' => $tarif,
            'total_price' => DB::raw("qty*$tarif")
        ]);
        $jo->update([
            'service_id' => $service,
            'work_order_detail_id' => $request->work_order_detail_id
        ]);
        $old_wod->update([
            'qty_leftover' => DB::raw("qty_leftover+$totalQty")
        ]);
        $wod->update([
            'qty_leftover' => DB::raw("qty_leftover-$totalQty")
        ]);
    } elseif (in_array($jo->service_type_id,[2,3,4])) {
//SEWA ARMADA
        $totalQty=$jo->total_unit;
        if ($totalQty>$wod->qty_leftover) {
            return Response::json(['message' => 'Qty pada Work Order tidak mencukupi, silahkan menambahkan Qty pada item Work Order!'],500);
        }

        if ($wod->quotation_detail_id) {
            $tarif=$wod->quotation_detail->price_contract_full;
            $service=$wod->quotation_detail->service_id;
        } else {
            $tarif=$wod->price_list->price_full;
            $service=$wod->price_list->service_id;
        }
        $detail->whereRaw('total_price > 0')->update([
            'total_price' => $tarif
        ]);
        $jo->update([
            'price' => $tarif,
            'total_price' => DB::raw("total_unit*$tarif"),
            'service_id' => $service,
            'work_order_detail_id' => $request->work_order_detail_id
        ]);
        $old_wod->update([
            'qty_leftover' => DB::raw("qty_leftover+$totalQty")
        ]);
        $wod->update([
            'qty_leftover' => DB::raw("qty_leftover-$totalQty")
        ]);

    }
    DB::commit();
    return Response::json(null,200);
}

public function cost_journal(Request $request)
{
    DB::beginTransaction();
    $jo=DB::table('job_orders')->where('id', $request->id)->first();
    $cst=DB::table('job_order_costs')
    ->leftJoin('cost_types','cost_types.id','job_order_costs.cost_type_id')
    ->leftJoin('accounts','accounts.id','cost_types.akun_kas_hutang')
    ->where('header_id', $request->id)
    ->where('status', 8)
    ->selectRaw('
        job_order_costs.id,
        job_order_costs.cost_type_id,
        job_order_costs.vendor_id,
        job_order_costs.total_price,
        cost_types.name,
        cost_types.akun_biaya,
        cost_types.akun_kas_hutang,
        accounts.no_cash_bank
        ')
    ->get();
    if (count($cst)<1) {
        return Response::json(['message' => 'Tidak ada biaya job order yang disetujui atasan!'],500,[],JSON_NUMERIC_CHECK);
    }
    $j=Journal::create([
        'company_id' => $jo->company_id,
        'type_transaction_id' => 50,
        'date_transaction' => Carbon::now(),
        'created_by' => auth()->id(),
        'code' => $jo->code,
        'status' => 2,
        'description' => "Biaya Job Order - $jo->code",
        'debet' => 0,
        'credit' => 0,
    ]);
    $hutang=0;
    foreach ($cst as $value) {
        JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => $value->akun_biaya,
            'debet' => $value->total_price,
            'credit' => 0,
            'description' => "Biaya JO - $value->name"
        ]);
        JournalDetail::create([
            'header_id' => $j->id,
            'account_id' => $value->akun_kas_hutang,
            'debet' => 0,
            'credit' => $value->total_price,
            'description' => "Biaya JO - $value->name"
        ]);
        JobOrderCost::find($value->id)->update([
            'status' => 5,
            'journal_id' => $j->id
        ]);

        if (!in_array($value->no_cash_bank,[1,2])) {
// akun hutang, tambah nominal hutang
            $p=Payable::create([
                'company_id' => $jo->company_id,
                'contact_id' => $jo->customer_id,
                'type_transaction_id' => 50,
                'journal_id' => $j->id,
                'relation_id' => $value->id,
                'created_by' => Auth::id(),
                'code' => $jo->code,
                'date_transaction' => Carbon::now(),
                'date_tempo' => Carbon::now(),
                'description' => "Biaya JO - $jo->code - $value->name",
                'is_invoice' => 0
            ]);
            PayableDetail::create([
                'header_id' => $p->id,
                'journal_id' => $j->id,
                'type_transaction_id' => 50,
                'relation_id' => $value->id,
                'code' => $jo->code,
                'date_transaction' => Carbon::now(),
                'credit' => $value->total_price,
                'description' => "Biaya JO - $jo->code - $value->name",
                'is_journal' => 1
            ]);
        }
    }
    DB::commit();

    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
}

public function submit_armada_lcl(Request $request,$id)
{
    DB::beginTransaction();
    $jo=DB::table('job_orders')->where('id', $id)->first();

    $code = new TransactionCode($jo->company_id, 'manifest');
    $code->setCode();
    $trx_code = $code->getCode();

    $save=[
        'company_id' => $jo->company_id,
        'transaction_type_id' => 22,
        'vehicle_type_id' => $jo->vehicle_type_id,
        'container_type_id' => $jo->container_type_id,
        'route_id' => $jo->route_id,
        'moda_id' => $jo->moda_id,
        'create_by' => Auth::id(),
        'date_manifest' => Carbon::now(),
        'status' => 1,
        'status_cost' => 1,
        'is_full' => 0,
        'code' => $trx_code
    ];
    if ($jo->vehicle_type_id) {
        $save['is_container']=0;
    } else {
        $save['is_container']=1;
    }
    $m=Manifest::create($save);
    foreach ($request->detail as $value) {
        $v=(object)$value;
        ManifestDetail::create([
            'header_id' => $m->id,
            'job_order_detail_id' => $v->detail_id,
            'create_by' => auth()->id(),
            'update_by' => auth()->id(),
            'transported' => $v->angkut,
            'leftover' => 0,
        ]);
        JobOrderDetail::find($v->detail_id)->update([
            'leftover' => ($v->leftover-$v->angkut)
        ]);
    }

    DB::commit();

    return Response::json(['message' => 'Packing List Berhasil Dibuat. Kode PL : '.$trx_code],200,[],JSON_NUMERIC_CHECK);
}

public function print_out(Request $request)
{
    if($request->id && !$request->uniqid) {
        $jo = JobOrder::find($request->id);

        if(!$jo->uniqid)
            $jo->update(['uniqid' => str_random(100)]);
    } else {
        $jo = JobOrder::where('uniqid', $request->uniqid)->first();
    }

    if (!$jo) {
        return view('layouts.404');
    }

    $data['item']=DB::table('job_orders')
    ->leftJoin('work_orders','work_orders.id','job_orders.work_order_id')
    ->leftJoin('services','services.id','job_orders.service_id')
    ->leftJoin('service_groups','service_groups.id','services.service_group_id')
    ->leftJoin('kpi_statuses','kpi_statuses.id','job_orders.kpi_id')
    ->leftJoin('routes as trayek','trayek.id','job_orders.route_id')
    ->leftJoin('contacts as customer','customer.id','job_orders.customer_id')
    ->leftJoin('contacts as sender','sender.id','job_orders.sender_id')
    ->leftJoin('contacts as receiver','receiver.id','job_orders.receiver_id')
    ->where('job_orders.id', $jo->id)
    ->selectRaw('
        job_orders.*,
        work_orders.code as wo_code,
        customer.name as customer_name,
        sender.name as sender_name,
        sender.address as sender_address,
        receiver.name as receiver_name,
        receiver.address as receiver_address,
        services.name as service,
        trayek.name as trayek,
        kpi_statuses.name as status_name,
        service_groups.name as service_type
        ')
    ->first();

    $data['detail']=DB::table('job_order_details')
    ->leftJoin('pieces','pieces.id','job_order_details.piece_id')
    ->where('job_order_details.header_id', $jo->id)
    ->selectRaw('
        job_order_details.*,
        pieces.name as piece
        ')->get();
    $sql="
    SELECT
    manifests.id,
    manifests.code,
    contacts.name as driver,
    vehicles.nopol,
    vehicle_types.name as vname,
    CONCAT(container_types.code,' - ',container_types.name) as cname,
    IF(manifests.status=1,'Packing List',IF(manifests.status=2,'Berangkat',IF(manifests.status=3,'Sampai','Selesai'))) as status_name,
    voyage_schedules.eta,
    voyage_schedules.etd,
    containers.stuffing,
    containers.stripping,
    manifests.depart,
    manifests.arrive,
    manifests.container_id,
    CONCAT(IFNULL(vessels.name,''),' - ',IFNULL(voyage_schedules.voyage,'')) as voyage,
    CONCAT(IFNULL(container_types.code,''),' - ',IFNULL(containers.container_no,'')) as container
    FROM
    manifests
    LEFT JOIN manifest_details ON manifest_details.header_id = manifests.id
    LEFT JOIN vehicles ON manifests.vehicle_id = vehicles.id
    LEFT JOIN contacts ON contacts.id = manifests.driver_id
    LEFT JOIN vehicle_types ON vehicle_types.id = manifests.vehicle_type_id
    LEFT JOIN container_types ON container_types.id = manifests.container_type_id
    LEFT JOIN containers ON containers.id = manifests.container_id
    LEFT JOIN voyage_schedules ON voyage_schedules.id = containers.voyage_schedule_id
    LEFT JOIN vessels ON vessels.id = voyage_schedules.vessel_id
    WHERE
    manifest_details.job_order_detail_id IN ( SELECT id FROM job_order_details WHERE header_id = $jo->id )
    GROUP BY
    manifests.id
    ";
    $data['manifest']=DB::select($sql);
// dd($data);
    $qr=QrCode::format('png')->size(100)->generate(url('/shipment').'?uniqid='.$jo->uniqid);
    $data['qr']=$qr;
// dd($data);
// return base64_encode($qr);
    return view('operational.job_order.print',$data);
}

}
