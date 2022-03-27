<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\WorkOrder;
use App\Model\WorkOrderDraft;
use App\Model\WorkOrderDetail;
use App\Model\JobOrder;
use App\Model\Company;
use App\Model\Contact;
use App\Model\Service;
use App\Model\Quotation;
use App\Model\QuotationDetail;
use App\Model\PriceList;
use App\Model\CustomerPrice;
use App\Model\Notification;
use App\Model\NotificationUser;
use App\Model\KpiStatus;
use App\Utils\TransactionCode;
use DB;
use Response;
use PDF;
use Carbon\Carbon;
use Schema;
use App\Abstracts\WorkOrder AS WO;
use App\Abstracts\JobOrder AS JO;
use Exception;

class WorkOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['services']=Service::with('service_type')->get();
      $data['draft']=DB::table('work_order_drafts')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 07-09-2020
      Description : Menyimpan work order
      Developer : Didin
      Status : Create
    */
    public function save($request) {
        DB::beginTransaction();
        try {
            $code = new TransactionCode($request->company_id, 'workOrder');
              $code->setCode();
              $trx_code = $code->getCode();
              $resp = [];
              $i=WorkOrder::create([
                'name' => $request->name,
                'date' => dateDB($request->date),
                'customer_id' => $request->customer_id,
                'company_id' => $request->company_id,
                'code' => $trx_code,
                'quotation_id' => $request->contract_id,
                'no_bl' => $request->no_bl,
                'aju_number' => $request->aju_number,
                'qty' => $request->qty,
                'is_job_packet' => $request->is_job_packet ?? 0,
                'updated_by' => auth()->id()
              ]);
              $resp['id'] = $i->id;
              $resp['details'] = [];
              $skip=false;
              if ($request->id_draft) {
                WorkOrderDraft::find($request->id_draft)->update([
                  'is_done' => 1
                ]);
              }
              foreach ($request->detail as $value) {
                if (empty($value)) {
                  continue;
                }
                if ($value['include']==1) {

                    $work_order_detail = WorkOrderDetail::create([
                      'header_id' => $i->id,
                      'quotation_detail_id' => $value['quotation_detail_id']??null,
                      'price_list_id' => $value['price_list_id']??null
                    ]);
                    $resp['details'][] = ['id' => $work_order_detail->id];
                    if ($skip) {
                      continue;
                    }

                    if ($value['price_list_id']??null) {
                      $pl=PriceList::find($value['price_list_id']);
                      if ($pl->service_type_id==4) {
                        WorkOrder::find($i->id)->update([
                          'price_list_id' => $request->price_list_id
                        ]);
                        $skip=true;
                      }
                    }

                    if ($value['quotation_detail_id']??null) {
                      $pl=QuotationDetail::find($value['quotation_detail_id']);
                      if ($pl->service_type_id==4) {
                        WorkOrder::find($i->id)->update([
                          'quotation_detail_id' => $value['quotation_detail_id']
                        ]);
                        $skip=true;
                      }
                    }
                  
                }
              }
              WO::storeAdditional($request->additional, $i->id);
              $customer=Contact::find($request->customer_id);
              $slug=str_random(6);
              $userList=DB::table('notification_type_users')
              ->leftJoin('users','users.id','=','notification_type_users.user_id')
              ->whereRaw("notification_type_users.notification_type_id = 11")
              ->select('users.id','users.is_admin','users.company_id')->get();
              $notification = [
                'notification_type_id' => 11,
                'name' => 'Work Order Baru telah Dibuat!',
                'description' => 'No. WO '.$trx_code.' nama customer '.$customer->name,
                'slug' => $slug,
                'route' => "marketing.work_order.show",
                'parameter' => json_encode(['id' => $i->id])
              ];
              //return Response::json($notification);
              $n=Notification::create($notification);
              foreach ($userList as $un) {
                if ($un->company_id==$request->company_id) {
                  NotificationUser::create([
                    'notification_id' => $n->id,
                    'user_id' => $un->id
                  ]);
                }
              }

              $this->storeJobPacket($i->id);
              if($request->filled('work_order_id')) {
                    $this->saveAs($i->id, $request->work_order_id);
              }
              DB::commit();
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
      
      return $resp;
    }

    /*
      Date : 27-08-2020
      Description : Meng-generate paket job order
      Developer : Didin
      Status : Create
    */
    public function storeJobPacket($work_order_id) {
        $workOrder = DB::table('work_orders')
        ->whereId($work_order_id)
        ->first();
        if($workOrder->is_job_packet == 1) {
            $workOrderDetails = DB::table('work_order_details')
            ->leftJoin('job_orders', 'job_orders.work_order_detail_id', 'work_order_details.id')
            ->whereRaw('work_order_details.id NOT IN (SELECT work_order_detail_id FROM job_packets)')
            ->where('work_order_details.header_id', $work_order_id)
            ->select('work_order_details.id', 'work_order_details.price_list_id', 'work_order_details.quotation_detail_id', 'job_orders.id AS job_order_id')
            ->orderBy('job_orders.id', 'DESC')
            ->get();
            if(count($workOrderDetails) > 0) {
                $start = DB::table('work_order_details')
                ->leftJoin('job_orders', 'job_orders.work_order_detail_id', 'work_order_details.id')
                ->whereRaw('work_order_details.id IN (SELECT work_order_detail_id FROM job_packets)')
                ->where('work_order_details.header_id', $work_order_id)
                ->select('job_orders.id AS job_order_id', 'work_order_details.price_list_id', 'work_order_details.quotation_detail_id')
                ->first();
                if($start == null) {
                    $start = $workOrderDetails[0];
                }
                $job_order_id = $start->job_order_id ?? null;
                $setting = new \App\Http\Controllers\Setting\SettingController();
                $woSetting = $setting->fetch('work_order', 'packet_service_id');
                $service_id = $woSetting->value;
                $service = DB::table('services')
                ->whereId($service_id)
                ->first();
                if($service == null) {
                    return response()->json(['message' => 'Layanan paket pekerjaan belum di-set di setting'], 421);
                }
                $service_type_id = $service->service_type_id;
                if(!$job_order_id) {
                    $kpiStatus = DB::table('kpi_statuses')
                    ->whereServiceId($service_id)
                    ->whereSortNumber(1)
                    ->first();
                    $params = [
                        'company_id' => $workOrder->company_id,
                        'customer_id' => $workOrder->customer_id,
                        'service_id' => $service_id,
                        'service_type_id' => $service_type_id,
                        'work_order_id' => $workOrder->id,
                        'work_order_detail_id' => $start->id,
                        'create_by' => auth()->id(),
                        'shipment_date' => $workOrder->date,
                        'price' => 1,
                        'status' => 0,
                        'total_unit' => 1,
                        'total_item' => 1,
                        'is_cancel' => 0,
                        'is_manifest' => 0,
                        'is_done' => 0,
                        'submit' => 0,
                        'is_operational_done' => 0,
                        'is_handling' => 0,
                        'is_warehouse' => 0,
                        'is_packaging' => 0,
                        'is_warehouserent' => 0,
                        'is_stuffing' => 0,
                        'kpi_id' => $kpiStatus->id
                    ];
                    $job_order_id = DB::table('job_orders') 
                    ->insertGetId($params);
                    JO::setDefaultStatus($job_order_id);
                    DB::table('kpi_logs')
                    ->insert([
                        'kpi_status_id' => $kpiStatus->id,
                        'job_order_id' => $job_order_id,
                        'company_id' => $workOrder->company_id,
                        'create_by' => auth()->id(),
                        'date_update' => Carbon::now()
                    ]);
                } 
                $params = [];
                foreach($workOrderDetails as $unit) {
                    $params[] = [
                        'work_order_detail_id' => $unit->id,
                        'job_order_id' => $job_order_id
                    ];
                }
                DB::table('job_packets')
                ->insert($params);

                return true;
            }
        }
    }

    /*
      Date : 27-08-2020
      Description : Meng-generate harga untuk layanan lcl, handling, stuffing, dan warehouserent
      Developer : Didin
      Status : Create
    */
    public function storeItemPacketPrice($work_order_id) {
        WO::storeItemPacketPrice($work_order_id);
    }
    
    /*
      Date : 06-03-2021
      Description : Menyimpan work order
      Developer : Didin
      Status : Edit
    */
    public function store(Request $request)
    {
        $request->validate([
            'company_id' => 'required',
            'customer_id' => 'required',
            'date' => 'required',
            'name' => 'required',
            'qty' => 'required'
        ]);
      
        try {
            $this->save($request);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 421);
        }

        return Response::json(['message' => 'Data berhasil disimpan']);
    }

    /*
      Date : 14-04-2020
      Description : Menampilkan detail work order
      Developer : Didin
      Status : Edit
    */
    public function show($id)
    {
        $data['item'] = WO::show($id);
        $data['detail_jo']=JobOrder::with('service_type','kpi_status','service')->where('work_order_id', $id)->get();
        $data['cost_detail']=DB::table('view_work_order_costs')->where('id', $id)->first();
        $data['detail']=WorkOrderDetail::with(
        'quotation_detail.service',
        'quotation_detail.route',
        'quotation_detail.commodity',
        'quotation_detail.vehicle_type',
        'quotation_detail.container_type',
        'quotation_detail.piece',
        'price_list.service',
        'price_list.route',
        'price_list.commodity',
        'price_list.vehicle_type',
        'price_list.container_type',
        'price_list.piece'
        )->where('header_id', $id)->leftJoin(DB::raw("(select work_order_detail_id,count(id) as total_jo from job_orders group by work_order_detail_id) Y"),"work_order_details.id","=","Y.work_order_detail_id")->get();
        WO::storePacketPrice($id);
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function print($id)
    {
      $data['item']=WorkOrder::with('quotation','customer.company')->where('id', $id)->first();
      $data['detail']=WorkOrderDetail::with(
        'quotation_detail.service',
        'quotation_detail.route',
        'quotation_detail.commodity',
        'quotation_detail.vehicle_type',
        'quotation_detail.container_type',
        'quotation_detail.piece',
        'price_list.service',
        'price_list.route',
        'price_list.commodity',
        'price_list.vehicle_type',
        'price_list.container_type',
        'price_list.piece'
        )->where('header_id', $id)->leftJoin(DB::raw("(select work_order_detail_id,count(id) as total_jo from job_orders group by work_order_detail_id) Y"),"work_order_details.id","=","Y.work_order_detail_id")->get();
      return PDF::loadView('pdf.work_order', $data)->stream();
      // return view('pdf.work_order', $data);
    }
    
    public function edit($id)
    {
        $data['item'] = WO::show($id);
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'date' => 'required',
            'name' => 'required',
        ],[
            'name.required' => 'Nama Pekerjaan wajib diisi!',
        ]);
        DB::beginTransaction();

        $i=WorkOrder::find($id)->update([
            'name' => $request->name,
            'date' => dateDB($request->date),
            'no_bl' => $request->no_bl,
            'aju_number' => $request->aju_number,
        ]);
        WO::storeAdditional($request->additional, $id);
        DB::commit();

      return Response::json(null);
    }

    /*
      Date : 07-09-2020
      Description : Menghapus work order
      Developer : Didin
      Status : Edit
    */
    public function destroy($id)
    {
      DB::beginTransaction();
      $wo = WorkOrder::find($id);
      if($wo->invoice_id != null) {
         return Response::json(['message' => 'Work order ini tidak bisa dihapus karena sudah mempunyai invoice'], 421);
      }
      if($wo->is_job_packet == 1) {
          $job_order_id = $this->fetchJobOrderIdPacket($id);
          DB::table('job_order_details')
          ->whereHeaderId($job_order_id)
          ->delete();
          DB::table('kpi_logs')
          ->whereJobOrderId($job_order_id)
          ->delete();
          DB::table('job_orders')
          ->whereId($job_order_id)
          ->delete();
      } else {
          $jobOrder = DB::table('job_orders')
          ->whereWorkOrderId($id)
          ->count();
          if($jobOrder > 0) {
             return Response::json(['message' => 'Work order ini tidak bisa dihapus karena sudah mempunyai job order'], 421);
          }
      }
      $wo->delete();
      DB::commit();
     return Response::json(['message' => 'Data berhasil dihapus']);
    }

    public function cari_detail_kontrak($id)
    {
      $data['item']=Quotation::find($id);
      $data['detail']=QuotationDetail::with('commodity','service','piece','route','moda','vehicle_type','container_type')->where('header_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function add_detail($id)
    {
      $wo=WorkOrder::find($id);
      $data=QuotationDetail::with('commodity','service','route','moda','vehicle_type','container_type')->where('header_id', $wo->quotation_id)->where('service_type_id','!=',4)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_detail(Request $request, $id)
    {
      DB::beginTransaction();
      if($request->quotation_detail_id != null) {
        $source = QuotationDetail::with('service' , 'service.service_type')->find($request->quotation_detail_id);
        $service_name = $source->service->name;
        $service_type_name = $source->service->service_type->name;
      }
      else {
        $source = PriceList::with('service', 'service.service_type')->find($request->price_list_id);
        $service_name = $source->service->name;
        $service_type_name = $source->service->service_type->name;
      }

      WorkOrderDetail::create([
        'header_id' => $id,
        'quotation_detail_id' => $request->quotation_detail_id,
        'price_list_id' => $request->price_list_id,
        'service_name' => $service_name,
        'service_type_name' => $service_type_name
      ]);
      DB::commit();
      $this->storeJobPacket($id);
      $this->storeItemPacketPrice($id);
      return Response::json(null);
    }

    public function store_detail_customer_price(Request $request, $id)
    {
      DB::beginTransaction();
      WorkOrderDetail::create([
        'header_id' => $id,
        'customer_price_id' => $request->id,
        'price_full' => $request->price_full,
        'service_name' => $request->service['name'],
        'service_type_name' => $request->service_type['name'],
        'piece_name' => $request->piece['name'],
        'container_type_name' => $request->container_type['name']
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      $wod = WorkOrderDetail::find($id);
      if($wod->header->is_job_packet == 1) {
         $other = DB::table('work_order_details')
         ->where('id', '!=', $id)
         ->whereHeaderId($wod->header_id)
         ->first();

         DB::table('job_orders')
         ->whereWorkOrderDetailId($id)
         ->update([
            'work_order_detail_id' => $other->id ?? null
         ]);
      }
      $work_order_id = $wod->header_id;
      $wod->delete();
      $this->storeItemPacketPrice($work_order_id);
      DB::commit();
    }

    public function store_edit_detail(Request $request)
    {
      $request->validate([
        'wod_id' => 'required',
        'qty' => 'required|min:1'
      ], [
        'qty.min' => 'Qty minimal diisi 1'
      ]);
      DB::beginTransaction();
      $wod=WorkOrderDetail::find($request->wod_id);
      if ($request->qty>=$wod->qty) {
        $left=$wod->qty_leftover+($request->qty-$wod->qty);
      } else {
        $left=$wod->qty_leftover-($wod->qty-$request->qty);
      }
      WorkOrderDetail::find($request->wod_id)->update([
        'description' => $request->description??'-',
        'qty_leftover' => $left,
        'qty' => $request->qty,
      ]);
      $this->storeContainerPrice($request->wod_id);
      DB::commit();
      return Response::json(null);
    }

    public function approve_detail($id)
    {
      DB::beginTransaction();
      WorkOrderDetail::find($id)->update([
        'is_done' => 1
      ]);
      $wod = DB::table('work_order_details')
      ->whereId($id)
      ->first();
      if($wod) {
          $undone = DB::table('work_order_details')
          ->whereIsDone(0)
          ->whereHeaderId($wod->header_id)
          ->count('id');
          if($undone == 0) {
            DB::table('work_orders')
            ->whereId($wod->header_id)
            ->update(['status' => 2]);
          } else {
            DB::table('work_orders')
            ->whereId($wod->header_id)
            ->update(['status' => 1]);            
          }
      }
      DB::commit();

      return Response::json(null);
    }

    public function store_qty(Request $request,$id)
    {
      DB::beginTransaction();
      WorkOrder::find($id)->update([
        'qty' => $request->qty
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function store_draft(Request $request)
    {
      $request->validate([
        'customer_id' => 'required',
        'description' => 'required',
      ],[
        'description.required' => 'Deskripsi Pekerjaan Work Order harus diisi!',
        'customer_id.required' => 'Customer Work Order harus diisi!'
      ]);
      DB::beginTransaction();
      WorkOrderDraft::create([
        'company_id' => $request->company_id,
        'customer_id' => $request->customer_id,
        'name' => $request->name,
        'no_bl' => $request->no_bl,
        'create_by' => auth()->id(),
        'aju_number' => $request->aju_number,
        'description' => $request->description,
        'date' => Carbon::parse($request->date),
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function show_request($id)
    {
      $data['item']=WorkOrderDraft::with('company','customer','user_create')->where('id', $id)->first();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function reject_request($id) {
        $workOrderDraft = WorkOrderDraft::findOrFail($id);
        $workOrderDraft->delete();

        return Response::json(['message' => 'Request berhasil ditolak'], 200, [], JSON_NUMERIC_CHECK);
    }

    public function cancel_done($id)
    {
      DB::beginTransaction();
      $wod=WorkOrderDetail::where('id', $id)->first();
      DB::table('work_orders')
      ->where('work_orders.id', $wod->header_id)
      ->update([
          'status' => 1
      ]);
      DB::table('work_order_details')
      ->where('work_order_details.id', $id)
      ->update([
          'is_done' => 0
      ]);
      DB::commit();
      return Response::json(null,200);
    }
    public function get_wo_detail_parameter($id)
    {
      $dt = DB::table('work_order_details as wod');
      $dt = $dt->leftJoin('quotation_details as qd','qd.id','wod.quotation_detail_id');
      $dt = $dt->leftJoin('price_lists as pl','pl.id','wod.price_list_id');
      $dt = $dt->where('wod.id', $id);
      $dt = $dt->selectRaw('
        wod.id as work_order_detail_id,
        wod.header_id as work_order_id,
        wod.quotation_detail_id,
        IFNULL(qd.header_id,NULL) as quotation_id,
        IFNULL(qd.service_id,pl.service_id) as service_id,
        IFNULL(qd.route_id,pl.route_id) as route_id,
        IFNULL(qd.commodity_id,pl.commodity_id) as commodity_id,
        IFNULL(qd.moda_id,pl.moda_id) as moda_id,
        IFNULL(qd.service_type_id,pl.service_type_id) as service_type_id,
        IFNULL(qd.vehicle_type_id,pl.vehicle_type_id) as vehicle_type_id,
        IFNULL(qd.container_type_id,pl.container_type_id) as container_type_id,
        IF(pl.id is not null,1,2) as type_tarif
      ')->first();
      return response()->json($dt,200,[],JSON_NUMERIC_CHECK);
    }

    public function fetchJobOrderIdPacket($id) {
        $jobPacket = DB::table('job_packets')
        ->join('work_order_details', 'work_order_details.id', 'job_packets.work_order_detail_id')
        ->where('work_order_details.header_id', $id)
        ->select('job_order_id')
        ->first();

        return $jobPacket->job_order_id ?? null;
    }

    /*
      Date : 27-08-2020
      Description : Mendapatkan id job order yang paketan
      Developer : Didin
      Status : Create
    */
    public function getJobOrderIdPacket($id) {
        $statusCode = 200;
        $resp = [
            'job_order_id' => null
        ];
        $workOrder = DB::table('work_orders')
        ->whereId($id)
        ->first();

        if($workOrder == null) {
            $statusCode = 404;
            $resp = [
                'message' => 'Data tidak ditemukan'
            ];
        } else {
            if($workOrder->is_job_packet == 1) {
                $jobOrderId = $this->fetchJobOrderIdPacket($id);
                if($jobOrderId == null) {
                    $statusCode = 421;
                    $resp = [
                        'message' => 'Tidak ada paket pekerjaan'
                    ];                
                } else {
                    $resp['job_order_id'] = $jobOrderId;
                }
            } else {
                $statusCode = 421;
                $resp = [
                    'message' => 'Work order bukan paket pekerjaan'
                ];                
            }
        }

        return Response::json($resp, $statusCode);
    }

    /*
      Date : 27-08-2020
      Description : Generate harga seluruh kontainer untuk work order yang bertipe paket pekerjaan
      Developer : Didin
      Status : Create
    */
    public function storeContainerPrice($work_order_detail_id) {
        $workOrder = DB::table('work_orders')
        ->join('work_order_details', 'work_order_details.header_id', 'work_orders.id')
        ->where('work_order_details.id', $work_order_detail_id)
        ->select('work_orders.id AS work_order_id', 'work_orders.is_job_packet', 'work_order_details.price_list_id', 'work_order_details.quotation_detail_id', 'work_order_details.qty')
        ->first();
        if($workOrder != null) {
            if($workOrder->is_job_packet == 1) {
                if($workOrder->price_list_id != null) {
                    $priceList = DB::table('price_lists')
                    ->whereId($workOrder->price_list_id)
                    ->first();
                    $service_type_id = $priceList->service_type_id;
                    $handling_type = $priceList->handling_type;
                    $price = $priceList->price_full;
                } else {
                    $quotationDetail = DB::table('quotation_details')
                    ->whereId($workOrder->quotation_detail_id)
                    ->first();
                    $service_type_id = $quotationDetail->service_type_id;
                    $handling_type = $quotationDetail->handling_type;
                    $price = $quotationDetail->price_inquery_full;
                }
                $totalPrice = 0;
                if($service_type_id == 2 || $service_type_id == 3) {
                    $totalPrice = $price * $workOrder->qty;
                } else if($service_type_id == 12 || $service_type_id == 13){
                    if($handling_type == 2) {
                        $totalPrice = $price * $workOrder->qty;
                    }
                }
                DB::table('job_packets')
                ->whereWorkOrderDetailId($work_order_detail_id)
                ->update([
                    'qty' => $workOrder->qty,
                    'price' => $price,
                    'total_price' => $totalPrice
                ]);
                $this->storePacketPrice($workOrder->work_order_id);
            }
        }
    }

    /*
      Date : 28-08-2020
      Description : Mengkalkulasi total harga packet
      Developer : Didin
      Status : Create
    */
      public function storePacketPrice($work_order_id) {
         WO::storePacketPrice($work_order_id);
      }

      /*
          Date : 07-09-2020
          Description : Meng-cloning work order
          Developer : Didin
          Status : Create
      */
      public function saveAs($new_work_order_id, $old_work_order_id) {
         if(Schema::hasTable('job_packets')) {
            $newWorkOrder = DB::table('work_orders')
            ->whereId($new_work_order_id)
            ->first();
            $oldWorkOrder = DB::table('work_orders')
            ->whereId($old_work_order_id)
            ->first();
            if($newWorkOrder->is_job_packet == 1 && $oldWorkOrder->is_job_packet == 1) {
                $oldJobOrderId = $this->fetchJobOrderIdPacket($old_work_order_id);
                $newJobOrderId = $this->fetchJobOrderIdPacket($new_work_order_id);
                $jobOrderDetails = DB::table('job_order_details')
                ->whereHeaderId($oldJobOrderId)
                ->get();
                $detailParams = [];
                foreach ($jobOrderDetails as $detail) {
                    $params = (array) $detail;
                    $params = collect($params)->except('id', 'header_id')->toArray();
                    $params['header_id'] = $newJobOrderId;
                    $detailParams[] = $params;
                }
                DB::table('job_order_details')
                ->insert($detailParams);

                JO::setReceiveDate($newJobOrderId);
                
                $kpiLogs = DB::table('kpi_logs')
                ->orderBy('id', 'asc')
                ->whereJobOrderId($oldJobOrderId)
                ->first();
                
                DB::table('job_orders')
                ->whereId($newJobOrderId)
                ->update([
                    'kpi_id' => $kpiLogs->kpi_status_id
                ]);

                $logParams = [];
                $logParams['job_order_id'] = $newJobOrderId;
                $logParams['kpi_status_id'] = $kpiLogs->kpi_status_id;
                $logParams['date_update'] = Carbon::now()->format('Y-m-d');
                DB::table('kpi_logs')->insert($logParams);

                $this->storeItemPacketPrice($new_work_order_id);
            }
         }
      }

    /*
      Date : 14-06-2020
      Description : Menampilkan detail layanan work order
      Developer : Didin
      Status : Create
    */
    public function showDetail($work_order_detail_id)
    {
        $wr = "work_order_details.id = $work_order_detail_id";
        $sql="
        select
        work_orders.customer_id,
        work_orders.company_id as company_id,
        work_orders.id as id_wo,
        work_orders.code as code,
        work_order_details.id as id_wod,
        work_order_details.qty_leftover,
        if(work_order_details.quotation_detail_id is not null,work_order_details.quotation_detail_id,work_order_details.price_list_id) as pq_id,
        if(work_order_details.quotation_detail_id is not null,1,2) as type_tarif,
        if(work_order_details.quotation_detail_id is not null,quotation_details.imposition,1) as imposition,
        if(service1.name is not null,service1.id,service2.id) as service_id,
        if(service1.name is not null,service1.name,service2.name) as service,
        if(trayek1.name is not null,trayek1.name,trayek2.name) as trayek,
        if(comm1.name is not null,comm1.id,comm2.id) as commodity_id,
        if(comm1.name is not null,comm1.name,comm2.name) as commodity,
        if(work_order_details.quotation_detail_id is not null,'Kontrak','Tarif Umum') as type_tarif_name,
        if(work_order_details.quotation_detail_id is not null,Y.imposition_name,X.imposition_name) as satuan
        from work_order_details
        left join work_orders on work_orders.id = work_order_details.header_id
        left join quotation_details on quotation_details.id = work_order_details.quotation_detail_id
        left join price_lists on price_lists.id = work_order_details.price_list_id
        left join services as service1 on service1.id = quotation_details.service_id
        left join services as service2 on service2.id = price_lists.service_id
        left join routes as trayek1 on trayek1.id = quotation_details.route_id
        left join routes as trayek2 on trayek2.id = price_lists.route_id
        left join commodities as comm1 on comm1.id = quotation_details.commodity_id
        left join commodities as comm2 on comm2.id = price_lists.commodity_id
        left join (select if(service_type_id in (6,7),pieces.name,if(service_type_id=2,'Kontainer',if(service_type_id=3,'Unit',impositions.name))) as imposition_name, quotation_details.id from quotation_details left join pieces on pieces.id = quotation_details.piece_id left join impositions on impositions.id = quotation_details.imposition group by quotation_details.id) Y on Y.id = work_order_details.quotation_detail_id
        left join (select if(service_type_id in (6,7),pieces.name,if(service_type_id=2,'Kontainer',if(service_type_id=3,'Unit','Kubikasi/Tonase/Item'))) as imposition_name, price_lists.id from price_lists left join pieces on pieces.id = price_lists.piece_id group by price_lists.id) X on X.id = work_order_details.price_list_id
        where $wr";
        $data['work_order_detail'] = DB::select($sql)[0];

        return Response::json($data);
    }

    /*
      Date : 14-09-2020
      Description : Menampikan rincian harga pada paket job order
      Developer : Didin
      Status : Create
    */
      public function showPriceDetail($work_order_id) {
          $workOrder = DB::table('work_orders')
          ->whereId($work_order_id)
          ->count('id');
          if($workOrder == 0) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
          }
          $data = WO::showPriceDetail($work_order_id);

          return response()->json($data);
      }
}
