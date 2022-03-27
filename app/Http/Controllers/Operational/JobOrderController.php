<?php

namespace App\Http\Controllers\Operational;

use ErrorException;
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
use App\Model\CashTransaction;
use App\Model\CashTransactionDetail;
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
use App\Utils\TransactionCode;
use Carbon\Carbon;
use DB;
use Response;
use File;
use Auth;
use QrCode;
use App\Jobs\HitungJoCostManifestJob;
use App\Abstracts\JobOrder AS JO;
use App\Abstracts\JobOrderDetail AS JOD;
use App\Abstracts\Operational\JobOrderCost AS JOC;
use App\Abstracts\Operational\Manifest AS M;
use App\Abstracts\Setting\Operational\CostType AS CT;
use App\Abstracts\WarehouseReceipt AS WR;
use Exception;
use App\Abstracts\AdditionalField;

class JobOrderController extends Controller
{

    /*
      Date : 24-03-2020
      Description : Membuat shipment status
      Developer : Didin
      Status : Create
    */
    public function storeShipmentStatus($warehouse_receipt_detail_id)
    {
        if($warehouse_receipt_detail_id != null) {
            $detail = DB::table('warehouse_receipt_details')
            ->whereId($warehouse_receipt_detail_id)
            ->first();

            if($detail == null) {
                return Response::json(['message' => 'ID Detail Penerimaan Barang Tidak Ditemukan'], 421);
            }

            $latest_shipment = DB::table('shipment_statuses')
            ->whereWarehouseReceiptId($detail->header_id)
            ->whereStatus(1)
            ->count('id');

            if($latest_shipment < 1) {
                DB::table('shipment_statuses')
                ->insert([
                    'warehouse_receipt_id' => $detail->header_id,
                    'status' => 1,
                    'status_date' => DB::raw('DATE_FORMAT(NOW(), "%Y-%m-%d")')
                ]);
            }
        }
    }

    protected function hitungQtyJo($woid) {
      $hitung=DB::select("select cekQtyWo({$woid}) as qtys")[0];
      return $hitung->qtys;
    }

    public function index()
    {
        $data['services']=Service::with('service_type', 'kpi_statuses')->get();
        $data['kpi_statuses']=DB::table('kpi_statuses')->whereRaw('1=1')->select('name')->groupBy('name')->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function create()
    {
    }

    /*
      Date : 29-02-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required',
            'type_tarif' => 'required',
            'service_type_id' => 'required',
            'work_order_detail_id' => 'required'
            ], [
            'customer_id.required' => 'Customer tidak boleh kosong'
        ]);

        DB::beginTransaction();
        try {
            $this->using_qty = \App\Http\Controllers\Setting\SettingController::fetch('work_order', 'using_qty');
            $workOrder = $this->storeWorkOrder($request);
            if($workOrder !== false) {
                $request->work_order_id = $workOrder['id'];
                $request->work_order_detail_id = $workOrder['details'][0]['id'];
            }

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
            } else if (in_array($request->service_type_id, [12, 13, 14, 15])) {
                $return=$this->save_type_warehouse($request);
            } else {
                return Response::json(['message' => 'Tipe Layanan Tidak ditemukan'],500);
            }
            $this->storeTransits($request->transits);
            $latest = DB::table('job_orders')
            ->orderBy('id', 'desc')
            ->first();
            JOD::doRequestOutbound($latest->id);
            JO::setDefaultStatus($latest->id);
            JO::store_price_list_cost($latest->id);
            JO::storeAdditional($request->additional, $latest->id);
            JO::countPrice($latest->id);
            JO::validasiLimitPiutang($latest->id);
            JOD::setContainerImposition();

            DB::commit();
            
            return $return;
        } catch (ErrorException $e) {
            DB::rollback();
            $msg = $e->getMessage();
            return response()->json(['message' => $msg], 421);
        }
    }

    public function storeTransits($transits, $job_order_id = null, $id = null) {
        if(!$job_order_id) {
            $jo = DB::table('job_orders')
            ->orderBy('id', 'desc')
            ->first();

            if($jo) {
                $job_order_id = $jo->id;
            }
        }
        if($job_order_id) {
            if(!$id) {
                DB::table('job_order_transits')
                ->whereHeaderId($job_order_id)
                ->delete();
            }
            if(is_array($transits)) {
                $transits = collect($transits)->map(function($e) use($job_order_id){
                    $e['header_id'] = $job_order_id;
                    if($e['date'] ?? null) {
                        $e['date'] = dateDB($e['date']);
                    }
                    return $e;
                })->toArray();
                DB::table('job_order_transits')
                ->insert($transits);
            }

        }
    }

    /*
      Date : 06-08-2020
      Description : Membuat work order dari job order yang memakai tarif umum dan kontrak
      Developer : Didin
      Status : Create
    */
    public function storeWorkOrder($request) {
        if($request->work_order_detail_id == -1) {
            $workOrder = new \App\Http\Controllers\Marketing\WorkOrderController();
            $service = DB::table('services')
            ->whereId($request->service_id)
            ->select('name', 'service_type_id')
            ->first();
            $customer = DB::table('contacts')
            ->whereId($request->customer_id)
            ->select('company_id')
            ->first();
            $params = [
                'name' => $service->name ?? '',
                'date' => Carbon::now()->format('d-m-Y'),
                'customer_id' => $request->customer_id,
                'company_id' => $customer->company_id,
                'qty' => 1,
                'type_tarif' => $request->type_tarif ?? 1,
                'detail' => [
                    [
                        'include' => 1,
                        'quotation_detail_id' => $request->quotation_detail_id ?? null,
                        'price_list_id' => $request->price_list_id ?? null,
                    ]
                ]
            ];

            $saved = $workOrder->save(new Request($params));
            foreach($saved['details'] as $d) {
                $params = [
                    'wod_id' => $d['id'],
                ];
                if($service->service_type_id == 1) {
                    $total = 0;
                    foreach($request->detail as $value) {
                        if ($value['imposition']==1) {
                              $total+=$value['total_volume'];
                        } elseif ($value['imposition']==2) {
                              $total+=$value['total_tonase'];
                        } else {
                              $total+=$value['total_item'];
                        }
                    }
                    $params['qty'] = $total;

                } else {
                    $params['qty'] = $request->total_unit;
                }
                $workOrder->store_edit_detail(new Request($params));
            }

            return $saved;
        } else {
            return false;
        }
    }

    /*
      Date : 10-03-2020
      Description : Menyimpan job order dengan layanan pengiriman per trip
      Developer : Didin
      Status : Create
    */
    public function save_type_3($request)
    {
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
            // 'wo_customer' => 'required',
            'detail' => 'required',
            'total_unit' => 'required|integer|min:1',
            'service_id' => 'required',
            'collectible_id' => 'required',
        ]);

        if ($request->type_tarif==2) {
            // $priceList=PriceList::whereRaw("service_type_id = 3 and route_id = $request->route_id and vehicle_type_id = $request->vehicle_type_id")->first();
            $priceList=PriceList::find($request->price_list_id);
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
        JobOrderCost::where('header_id', $jo->id)->delete();
        $wod=WorkOrderDetail::find($request->work_order_detail_id);
        $total=$wod->qty_leftover-$request->total_unit;
        if(($this->using_qty->value ?? null) == 1) {
            if ($total<0) {
                return Response::json(['message' => 'Item JO tidak boleh melebihi jumlah Work Order!'],500);
            }
        } else {
            $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
        }
        $wod->update([
            'qty_leftover' => $total
        ]);

        //simpan biaya Operasional di job order
        $costs=array();
        $qc=QuotationCost::where('quotation_detail_id', $request->quotation_detail_id)->get();
        if (isset($qc)) {
            foreach ($qc as $vls) {
                $joc=JobOrderCost::create([
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
                array_push($costs,[
                  'job_order_cost_id' => $joc->id,
                  'cost_type_id' => $vls->cost_type_id,
                  'transaction_type_id' => 21,
                  'vendor_id' => $vls->vendor_id,
                  'qty' => $request->total_unit,
                  'price' => $vls->total_cost,
                  'total_price' => ($vls->is_internal?0:$request->total_unit*$vls->total_cost),
                  'quotation_costs' => $request->total_unit*$vls->total_cost,
                  'description' => $vls->description,
                  'type' => 1,
                  'is_edit' => 1,
                  'create_by' => auth()->id()
                ]);
            }
        } else {
            $qt=RouteCost::whereRaw("route_id = $request->route_id AND commodity_id = $request->commodity_id AND vehicle_type_id = $request->vehicle_type_id")->get();
            foreach ($qt as $qts) {
                $qtn=RouteCostDetail::where('header_id', $qts->id)->get();
                foreach ($qtn as $qtt) {
                    $joc=JobOrderCost::create([
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
                    array_push($costs,[
                      'job_order_cost_id' => $joc->id,
                      'cost_type_id' => $qtt->cost_type_id,
                      'transaction_type_id' => 21,
                      'vendor_id' => $qtt->cost_type->vendor_id,
                      'qty' => $request->total_unit,
                      'price' => $qtt->cost,
                      'total_price' => ($qtt->is_internal?0:$qtt->cost*$request->total_unit),
                      'description' => $qtt->description,
                      'type' => 1,
                      'is_edit' => 1,
                      'quotation_costs' => $qtt->cost*$request->total_unit,
                      'create_by' => auth()->id()
                    ]);
                }
            }
        }

        $total_item=0;
        foreach ($request->detail as $key => $value) {
            if (empty($value)) {
                continue;
            }
            $jod = JobOrderDetail::create([
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
            $this->store_warehouse_item($value, $jod->id);
            $total_item++;
        }
        $ks=KpiStatus::whereRaw("service_id = $request->service_id")
        ->orderBy('sort_number','asc')
        ->first();
        if(!$ks) {
            return response()->json(['message' => 'Status process in this service is empty'], 421);
        }
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
            foreach ($costs as $value) {
              $insert = $value;
              $insert['header_id']=$m->id;
              DB::table('manifest_costs')->insert($insert);
            }
            // $this->manifest_costs($m->id, $request->route_id);
            $jod=JobOrderDetail::where('header_id', $jo->id)->get();
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

        return Response::json(null);
    }

    /*
      Date : 10-03-2020
      Description : Menyimpan job order dengan layanan transportasi
      Developer : Didin
      Status : Create
    */
    public function save_type_4($request)
    {
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
            // $priceList=PriceList::whereRaw("service_type_id = 4 and route_id = $request->route_id and vehicle_type_id = $request->vehicle_type_id")->first();
            $priceList=PriceList::find($request->price_list_id);
            if (empty($priceList)) {
                return Response::json(['message' => 'Tarif Umum dengan trayek dan armada ini tidak ditemukan'],500);
            }
            $price=$priceList->price_full;
            $total_price=$request->total_unit*$priceList->price_full;
        } else if ($request->type_tarif==3) {
            $customerPrice=DB::table('customer_prices')->whereId($wod->customer_price_id)->first();
            if (empty($customerPrice)) {
                return Response::json(['message' => 'Tarif customer dengan Layanan ini tidak ditemukan'],500);
            }
            $price=$customerPrice->price_full;
            $total_price=$request->total_unit*$customerPrice->price_full;
            // $total_price=*$price;
            } else {
                $quotationDetail=QuotationDetail::find($request->quotation_detail_id);
                $price=$quotationDetail->price_contract_full;
                $total_price=$request->total_unit*$quotationDetail->price_contract_full;
            }
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

            JobOrderCost::where('header_id', $jo->id)->delete();
            $total_item=0;
            foreach ($request->detail as $key => $value) {
                $jod = JobOrderDetail::create([
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

                $this->store_warehouse_item($value, $jod->id);
                $total_item++;
            }
            $ks=KpiStatus::whereRaw("service_id = $request->service_id")
            ->orderBy('sort_number','asc')
            ->first();
            if(!$ks) {
                return response()->json(['message' => 'Status process in this service is empty'], 421);
            }
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
            $costs=array();
            $qc=QuotationCost::where('quotation_detail_id', $request->quotation_detail_id)->get();
            if (isset($qc)) {
                foreach ($qc as $vls) {
                    $joc=JobOrderCost::create([
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
                    array_push($costs,[
                      'job_order_cost_id' => $joc->id,
                      'cost_type_id' => $vls->cost_type_id,
                      'transaction_type_id' => 21,
                      'vendor_id' => $vls->vendor_id,
                      'qty' => $request->total_unit,
                      'price' => $vls->total_cost,
                      'total_price' => ($vls->is_internal?0:$request->total_unit*$vls->total_cost),
                      'description' => $vls->description,
                      'type' => 1,
                      'is_edit' => 1,
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
                        $joc=JobOrderCost::create([
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
                        array_push($costs,[
                          'job_order_cost_id' => $joc->id,
                          'cost_type_id' => $qtt->cost_type_id,
                          'transaction_type_id' => 21,
                          'vendor_id' => $qtt->cost_type->vendor_id,
                          'qty' => $request->total_unit,
                          'price' => $qtt->cost,
                          'total_price' => ($qtt->is_internal?0:$qtt->cost*$request->total_unit),
                          'description' => $qtt->description,
                          'type' => 1,
                          'is_edit' => 1,
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
            foreach ($costs as $key => $value) {
              $insert = $value;
              $insert['header_id']=$m->id;
              DB::table('manifest_costs')->insert($insert);
            }
            // $this->manifest_costs($m->id, $request->route_id);
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

        return Response::json(null);
    }
    public function save_type_1($request)
    {
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
        'detail' => 'required',
        'service_id' => 'required',
        'collectible_id' => 'required',
      ],[
        'service_id.required_if' => 'Jenis Layanan harus dipilih jika tipe tarif dari tarif umum'
      ]);
      // dd($price);
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
        'agent_name' => $request->agent_name,
        'awb_number' => $request->awb_number,
        'flight_code' => $request->flight_code,
        'flight_route' => $request->flight_route,
        'flight_date' => dateDB($request->flight_date),
        'cargo_ready_date' => dateDB($request->cargo_ready_date),
        'house_awb' => $request->house_awb,
        'hs_code' => $request->hs_code,
        'uniqid' => str_random(100)
      ]);

      /*
      DISABLE
      Generate Biaya ada di PL untuk layanan LCL

      JobOrderCost::where('header_id', $jo->id)->delete();
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
      */
      $total_item=0;
      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        if ($request->type_tarif==2) {
          /*
          $priceList=PriceList::whereRaw("service_type_id = 1")
          ->whereModaId($request->moda_id)
          ->whereRouteId($request->route_id)
          ->whereVehicleTypeId($request->vehicle_type_id)
          ->first();
          */
          $priceList=PriceList::find($request->price_list_id);

          if (empty($priceList)) {
            return Response::json(['message' => 'Tarif Umum tidak ditemukan'],500);
          }

          if($priceList->min_type == 1) {
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
          }
          else {
            $priceListMinDetail = DB::table('price_list_minimum_details')->where('price_list_id', $priceList->id);

            if ($value['imposition']==1) {
              $priceListMinDetail = $priceListMinDetail->orderBy('min_m3')->get();
              foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                if(count($priceListMinDetail) == 1)
                {
                  $price=$valuePriceMin->price_per_m3;
                  $total_price=$price*$value['total_volume'];
                  break;
                }
                else
                {
                  if($keyPriceMin == 0)
                  {
                    if($value['total_volume'] < $valuePriceMin->min_m3)
                    {
                      $price=$valuePriceMin->price_per_m3;
                      $total_price=$price*$value['total_volume'];
                      break;
                    }
                    elseif($value['total_volume'] >= $valuePriceMin->min_m3 && $value['total_volume'] < $priceListMinDetail[$keyPriceMin + 1]->min_m3)
                    {
                      $price=$valuePriceMin->price_per_m3;
                      $total_price=$price*$value['total_volume'];
                      break;
                    }
                  }
                  elseif($keyPriceMin == count($priceListMinDetail)-1)
                  {
                    $price=$valuePriceMin->price_per_m3;
                    $total_price=$price*$value['total_volume'];
                    break;
                  }
                  else {
                    if($value['total_volume'] >= $valuePriceMin->min_m3 && $value['total_volume'] < $priceListMinDetail[$keyPriceMin + 1]->min_m3)
                    {
                      $price=$valuePriceMin->price_per_m3;
                      $total_price=$price*$value['total_volume'];
                      break;
                    }
                  }
                }
              }
            } elseif ($value['imposition']==2) {
              $priceListMinDetail = $priceListMinDetail->orderBy('min_kg')->get();
              foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                if(count($priceListMinDetail) == 1)
                {
                  $price=$valuePriceMin->price_per_kg;
                  $total_price=$price*$value['total_tonase'];
                  break;
                }
                else
                {
                  if($keyPriceMin == 0)
                  {
                    if($value['total_tonase'] < $valuePriceMin->min_kg)
                    {
                      $price=$valuePriceMin->price_per_kg;
                      $total_price=$price*$value['total_tonase'];
                      break;
                    }
                    elseif($value['total_tonase'] >= $valuePriceMin->min_kg && $value['total_tonase'] < $priceListMinDetail[$keyPriceMin + 1]->min_kg)
                    {
                      $price=$valuePriceMin->price_per_kg;
                      $total_price=$price*$value['total_tonase'];
                      break;
                    }
                  }
                  elseif($keyPriceMin == count($priceListMinDetail)-1)
                  {
                    $price=$valuePriceMin->price_per_kg;
                    $total_price=$price*$value['total_tonase'];
                    break;
                  }
                  else {
                    if($value['total_tonase'] >= $valuePriceMin->min_kg && $value['total_tonase'] < $priceListMinDetail[$keyPriceMin + 1]->min_kg)
                    {
                      $price=$valuePriceMin->price_per_kg;
                      $total_price=$price*$value['total_tonase'];
                      break;
                    }
                  }
                }
              }
            } else {
              $priceListMinDetail = $priceListMinDetail->orderBy('min_item')->get();
              foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                if(count($priceListMinDetail) == 1)
                {
                  $price=$valuePriceMin->price_per_item;
                  $total_price=$price*$value['total_item'];
                  break;
                }
                else
                {
                  if($keyPriceMin == 0)
                  {
                    if($value['total_item'] < $valuePriceMin->min_item)
                    {
                      $price=$valuePriceMin->price_per_item;
                      $total_price=$price*$value['total_item'];
                      break;
                    }
                    elseif($value['total_item'] >= $valuePriceMin->min_item && $value['total_item'] < $priceListMinDetail[$keyPriceMin + 1]->min_item)
                    {
                      $price=$valuePriceMin->price_per_item;
                      $total_price=$price*$value['total_item'];
                      break;
                    }
                  }
                  elseif($keyPriceMin == count($priceListMinDetail)-1)
                  {
                    $price=$valuePriceMin->price_per_item;
                    $total_price=$price*$value['total_item'];
                    break;
                  }
                  else {
                    if($value['total_item'] >= $valuePriceMin->min_item && $value['total_item'] < $priceListMinDetail[$keyPriceMin + 1]->min_item)
                    {
                      $price=$valuePriceMin->price_per_item;
                      $total_price=$price*$value['total_item'];
                      break;
                    }
                  }
                }
              }
            }
          }
          // $total_price=*$price;
        } else {
          $quotationDetail=QuotationDetail::find($request->quotation_detail_id);

          if($quotationDetail->min_type == 1)
          {
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
          else
          {
            $priceListMinDetail = DB::table('price_list_minimum_details')->where('quotation_detail_id', $quotationDetail->id);

            if ($value['imposition']==1) {
              $priceListMinDetail = $priceListMinDetail->orderBy('min_m3')->get();
              foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                if(count($priceListMinDetail) == 1)
                {
                  $price=$valuePriceMin->price_per_m3;
                  $total_price=$price*$value['total_volume'];
                  break;
                }
                else
                {
                  if($keyPriceMin == 0)
                  {
                    if($value['total_volume'] < $valuePriceMin->min_m3)
                    {
                      $price=$valuePriceMin->price_per_m3;
                      $total_price=$price*$value['total_volume'];
                      break;
                    }
                    elseif($value['total_volume'] >= $valuePriceMin->min_m3 && $value['total_volume'] < $priceListMinDetail[$keyPriceMin + 1]->min_m3)
                    {
                      $price=$valuePriceMin->price_per_m3;
                      $total_price=$price*$value['total_volume'];
                      break;
                    }
                  }
                  elseif($keyPriceMin == count($priceListMinDetail)-1)
                  {
                    $price=$valuePriceMin->price_per_m3;
                    $total_price=$price*$value['total_volume'];
                    break;
                  }
                  else {
                    if($value['total_volume'] >= $valuePriceMin->min_m3 && $value['total_volume'] < $priceListMinDetail[$keyPriceMin + 1]->min_m3)
                    {
                      $price=$valuePriceMin->price_per_m3;
                      $total_price=$price*$value['total_volume'];
                      break;
                    }
                  }
                }
              }
            } elseif ($value['imposition']==2) {
              $priceListMinDetail = $priceListMinDetail->orderBy('min_kg')->get();
              foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                if(count($priceListMinDetail) == 1)
                {
                  $price=$valuePriceMin->price_per_kg;
                  $total_price=$price*$value['total_tonase'];
                  break;
                }
                else
                {
                  if($keyPriceMin == 0)
                  {
                    if($value['total_tonase'] < $valuePriceMin->min_kg)
                    {
                      $price=$valuePriceMin->price_per_kg;
                      $total_price=$price*$value['total_tonase'];
                      break;
                    }
                    elseif($value['total_tonase'] >= $valuePriceMin->min_kg && $value['total_tonase'] < $priceListMinDetail[$keyPriceMin + 1]->min_kg)
                    {
                      $price=$valuePriceMin->price_per_kg;
                      $total_price=$price*$value['total_tonase'];
                      break;
                    }
                  }
                  elseif($keyPriceMin == count($priceListMinDetail)-1)
                  {
                    $price=$valuePriceMin->price_per_kg;
                    $total_price=$price*$value['total_tonase'];
                    break;
                  }
                  else {
                    if($value['total_tonase'] >= $valuePriceMin->min_kg && $value['total_tonase'] < $priceListMinDetail[$keyPriceMin + 1]->min_kg)
                    {
                      $price=$valuePriceMin->price_per_kg;
                      $total_price=$price*$value['total_tonase'];
                      break;
                    }
                  }
                }
              }
            } else {
              $priceListMinDetail = $priceListMinDetail->orderBy('min_item')->get();
              foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                if(count($priceListMinDetail) == 1)
                {
                  $price=$valuePriceMin->price_per_item;
                  $total_price=$price*$value['total_item'];
                  break;
                }
                else
                {
                  if($keyPriceMin == 0)
                  {
                    if($value['total_item'] < $valuePriceMin->min_item)
                    {
                      $price=$valuePriceMin->price_per_item;
                      $total_price=$price*$value['total_item'];
                      break;
                    }
                    elseif($value['total_item'] >= $valuePriceMin->min_item && $value['total_item'] < $priceListMinDetail[$keyPriceMin + 1]->min_item)
                    {
                      $price=$valuePriceMin->price_per_item;
                      $total_price=$price*$value['total_item'];
                      break;
                    }
                  }
                  elseif($keyPriceMin == count($priceListMinDetail)-1)
                  {
                    $price=$valuePriceMin->price_per_item;
                    $total_price=$price*$value['total_item'];
                    break;
                  }
                  else {
                    if($value['total_item'] >= $valuePriceMin->min_item && $value['total_item'] < $priceListMinDetail[$keyPriceMin + 1]->min_item)
                    {
                      $price=$valuePriceMin->price_per_item;
                      $total_price=$price*$value['total_item'];
                      break;
                    }
                  }
                }
              }
            }
          }
        }

        $wod=WorkOrderDetail::find($request->work_order_detail_id);;
        if ($value['imposition']==1) {
          $total=$wod->qty-$this->hitungQtyJo($wod->id)-$value['total_volume'];
          if(($this->using_qty->value ?? null) == 1) {
              if ($total<0) {
                return Response::json(['message' => 'Jumlah Item JO tidak boleh melebihi Work Order!'],500);
              }
          } else {
                $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
          }
          $wod->update([
            'qty_leftover' => $total
          ]);
        } elseif ($value['imposition']==2) {
          $total=$wod->qty-$this->hitungQtyJo($wod->id)-$value['total_tonase'];
          if(($this->using_qty->value ?? null) == 1) {
              if ($total<0) {
                return Response::json(['message' => 'Jumlah Item JO tidak boleh melebihi Work Order!'],500);
              }
          } else {
            $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
          }
          $wod->update([
            'qty_leftover' => $total
          ]);
        } else {
          $total=$wod->qty-$this->hitungQtyJo($wod->id)-$value['total_item'];
          if(($this->using_qty->value ?? null) == 1) {
              if ($total<0) {
                return Response::json(['message' => 'Jumlah Item JO tidak boleh melebihi Work Order!'],500);
              }
          } else {
                $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
          }
          $wod->update([
            'qty_leftover' => $total
          ]);
        }
        $jod = JobOrderDetail::create([
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
          'price' => $price ?? 0,
          'total_price' => $total_price ?? 0,
          'qty' => $value['total_item'],
          'volume' => $value['total_volume'],
          'weight' => $value['total_tonase'],
          'long' => $value['long'] ?? 0,
          'wide' => $value['wide'] ?? 0,
          'high' => $value['high'] ?? 0,
          'volumetric_weight' => $value['volumetric_weight'] ?? 0,
          'item_name' => $value['item_name'],
          'imposition' => $value['imposition'],
          'description' => $value['description'],
          'leftover' => $value['total_item']
        ]);

        $this->store_warehouse_item($value, $jod->id);
        $total_item++;
      }
      $ks=KpiStatus::whereRaw("service_id = $request->service_id")
        ->orderBy('sort_number','asc')
        ->first();
        if(!$ks) {
            return response()->json(['message' => 'Status process in this service is empty'], 421);
        }
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
      JO::adjustVolumetricWeight($jo->id);

      return Response::json(null);
    }

    /*
      Date : 24-08-2020
      Description : Menyimpan job order untuk tipe layanan handling, warehouserent, packaging, dan stuffing
      Developer : Didin
      Status : Create
    */
    public function save_type_warehouse($request)
    {
        $request->validate([
            'customer_id' => 'required',
            'type_tarif' => 'required',
            'service_type_id' => 'required',
            'service_id' => 'required_if:type_tarif,2',
            'quotation_detail_id' => 'required_if:type_tarif,1',
            'work_order_id' => 'required',
            'shipment_date' => 'required',
            'detail' => 'required',
            'service_id' => 'required',
        ],[
            'service_id.required_if' => 'Jenis Layanan harus dipilih jika tipe tarif dari tarif umum'
        ]);
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

      $total_item=0;
      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }

        $wod=WorkOrderDetail::find($request->work_order_detail_id);;
        if ($value['imposition']==1) {
          $total=$wod->qty-$this->hitungQtyJo($wod->id)-$value['total_volume'];
          if(($this->using_qty->value ?? null) == 1) {
              if ($total<0) {
                return Response::json(['message' => 'Jumlah Item JO tidak boleh melebihi Work Order!'],500);
              }
          } else {
                $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
          }
          $wod->update([
            'qty_leftover' => $total
          ]);
        } elseif ($value['imposition']==2) {
          $total=$wod->qty-$this->hitungQtyJo($wod->id)-$value['total_tonase'];
          if(($this->using_qty->value ?? null) == 1) {
              if ($total<0) {
                return Response::json(['message' => 'Jumlah Item JO tidak boleh melebihi Work Order!'],500);
              }
          } else {
            $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
          }
          $wod->update([
            'qty_leftover' => $total
          ]);
        } else {
          $total=$wod->qty-$this->hitungQtyJo($wod->id)-$value['total_item'];
          if(($this->using_qty->value ?? null) == 1) {
              if ($total<0) {
                return Response::json(['message' => 'Jumlah Item JO tidak boleh melebihi Work Order!'],500);
              }
          } else {
                $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
          }
          $wod->update([
            'qty_leftover' => $total
          ]);
        }
        $jod = JobOrderDetail::create([
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
          'price' => 0,
          'total_price' => 0,
          'qty' => $value['total_item'],
          'volume' => $value['total_volume'],
          'weight' => $value['total_tonase'],
          'item_name' => $value['item_name'],
          'imposition' => $value['imposition'],
          'description' => $value['description'],
          'leftover' => $value['total_item']
        ]);

        $this->store_warehouse_item($value, $jod->id);
        $total_item++;
      }
      
      $ks=KpiStatus::whereRaw("service_id = $request->service_id")
        ->orderBy('sort_number','asc')
        ->first();
        if(!$ks) {
            return response()->json(['message' => 'Status process in this service is empty'], 421);
        }
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

      if($request->service_type_id == 14) {
          if($wod->price_list_id != null) {
              $price = $priceList->price_full;
          }  else {
              $price = $quotationDetail->price_contract_full;
          }
          JobOrder::find($jo->id)->update([
                'price' => $price,
                'total_price' => $price
          ]);

      }

      return Response::json(null);
    }

    /*
      Date : 16-03-2020
      Description : Menambah data barang khusus barang dari warehouse
      Developer : Didin
      Status : Create
    */
    public function store_warehouse_item($value, $job_order_detail_id) {
        if(array_key_exists('warehouse_receipt_detail_id', $value)) {

            DB::table('job_order_details')
            ->whereId($job_order_detail_id)
            ->update([
                'warehouse_receipt_detail_id' => $value['warehouse_receipt_detail_id'],
                'item_id' => $value['item_id'],
                'rack_id' => $value['rack_id']
            ]);

            $this->storeShipmentStatus($value['warehouse_receipt_detail_id']);
        }
    }

    

    public function save_type_2($request)
    {
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
        'detail' => 'required',
        'total_unit' => 'required|integer|min:1',
        'service_id' => 'required',
        'collectible_id' => 'required',
      ], [
        'sender_id.required' => 'Pengirim tidak boleh kosong',
        'receiver_id.required' => 'Penerima tidak boleh kosong',
      ]);
      if ($request->type_tarif==2) {
        // $priceList=PriceList::whereRaw("service_type_id = 2 and route_id = $request->route_id and commodity_id = $request->commodity_id and container_type_id = $request->container_type_id")->first();
        $priceList=PriceList::find($request->price_list_id);
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

      JobOrderCost::where('header_id', $jo->id)->delete();
      $wod=WorkOrderDetail::find($request->work_order_detail_id);
      $total=$wod->qty_leftover-$request->total_unit;
      if(($this->using_qty->value ?? null) == 1) {
          if ($total<0) {
            return Response::json(['message' => 'Item JO tidak boleh melebihi jumlah Work Order!'],500);
          }
      } else {
            $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
      }
      $wod->update([
        'qty_leftover' => $total
      ]);

      $total_item=0;
      foreach ($request->detail as $key => $value) {
        if (empty($value)) {
          continue;
        }
        $jod = JobOrderDetail::create([
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
        $this->store_warehouse_item($value, $jod->id);
        $total_item++;
      }
      $ks=KpiStatus::whereRaw("service_id = $request->service_id")
        ->orderBy('sort_number','asc')
        ->first();
        if(!$ks) {
            return response()->json(['message' => 'Status process in this service is empty'], 421);
        }
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
      $costs=array();
      $qc=QuotationCost::where('quotation_detail_id', $request->quotation_detail_id)->get();
      if (isset($qc)) {
        foreach ($qc as $vls) {
          $joc=JobOrderCost::create([
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
          array_push($costs,[
            'job_order_cost_id' => $joc->id,
            'cost_type_id' => $vls->cost_type_id,
            'transaction_type_id' => 21,
            'vendor_id' => $vls->vendor_id,
            'qty' => $request->total_unit,
            'price' => $vls->total_cost,
            'total_price' => ($vls->is_internal?0:$request->total_unit*$vls->total_cost),
            'description' => $vls->description,
            'type' => 1,
            'is_edit' => 1,
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
            $joc=JobOrderCost::create([
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
            array_push($costs,[
              'job_order_cost_id' => $joc->id,
              'cost_type_id' => $qtt->cost_type_id,
              'transaction_type_id' => 21,
              'vendor_id' => $qtt->cost_type->vendor_id,
              'qty' => $request->total_unit,
              'price' => $qtt->cost,
              'total_price' => ($qtt->is_internal?0:$qtt->cost*$request->total_unit),
              'description' => $qtt->description,
              'type' => 1,
              'is_edit' => 1,
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
        foreach ($costs as $key => $value) {
          $insert = $value;
          $insert['header_id']=$m->id;
          DB::table('manifest_costs')->insert($insert);
        }
        // $this->manifest_cost($m->id, $request->route_id);
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
      return Response::json(['message' => 'Data successfully saved']);
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

      JobOrderCost::where('header_id', $jo->id)->delete();
      $wod=WorkOrderDetail::find($request->work_order_detail_id);
      $total=$wod->qty_leftover-$request->total_unit;
      if(($this->using_qty->value ?? null) == 1) {
          if ($total<0) {
            return Response::json(['message' => 'Item JO tidak boleh melebihi jumlah Work Order!'],500);
          }
      } else {
            $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
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
        $priceList=PriceList::find($wod->price_list_id);
        if (empty($priceList)) {
          return Response::json(['message' => 'Tarif Umum dengan Layanan ini tidak ditemukan'],500);
        }
        $price=$priceList->price_full;
        $total_price=$request->total_unit*$priceList->price_full;
        // $total_price=*$price;
      } else if ($request->type_tarif==3) {
        $customerPrice=DB::table('customer_prices')->whereId($wod->customer_price_id)->first();
        if (empty($customerPrice)) {
          return Response::json(['message' => 'Tarif customer dengan Layanan ini tidak ditemukan'],500);
        }
        $price=$customerPrice->price_full;
        $total_price=$request->total_unit*$customerPrice->price_full;
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

      $ks=KpiStatus::whereRaw("service_id = $request->service_id")
        ->orderBy('sort_number','asc')
        ->first();
        if(!$ks) {
            return response()->json(['message' => 'Status process in this service is empty'], 421);
        }
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


      return Response::json(null);
    }
    public function save_type_7($request)
    {
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
        // 'wo_customer' => 'required',
        'service_id' => 'required',
        //'collectible_id' => 'required',
        'total_unit' => 'required',
      ]);

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

      JobOrderCost::where('header_id', $jo->id)->delete();
      $wod=WorkOrderDetail::find($request->work_order_detail_id);
      $total=$wod->qty_leftover-$request->total_unit;
      if(($this->using_qty->value ?? null) == 1) {
          if ($total<0) {
            return Response::json(['message' => 'Item JO tidak boleh melebihi jumlah Work Order!'],500);
          }
      } else {
            $wod->increment('qty', ($total - $wod->qty_leftover) * -1);
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
        $priceList=PriceList::whereId($request->price_list_id)->first();
        if (empty($priceList)) {
          return Response::json(['message' => 'Tarif Umum dengan Layanan ini tidak ditemukan'],500);
        }
        $price=$priceList->price_full;
        $total_price=$request->total_unit*$priceList->price_full;
        // $total_price=*$price;
      } else if ($request->type_tarif==3) {
        $customerPrice=DB::table('customer_prices')->whereId($wod->customer_price_id)->first();
        if (empty($customerPrice)) {
          return Response::json(['message' => 'Tarif customer dengan Layanan ini tidak ditemukan'],500);
        }
        $price=$customerPrice->price_full;
        $total_price=$request->total_unit*$customerPrice->price_full;
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

      $ks=KpiStatus::whereRaw("service_id = $request->service_id")
        ->orderBy('sort_number','asc')
        ->first();
        if(!$ks) {
            return response()->json(['message' => 'Status process in this service is empty'], 421);
        }
        
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

        return Response::json(null);
    }

    /*
      Date : 16-03-2020
      Description : Menampilkan detail job order
      Developer : Didin
      Status : Edit
    */
    public function show($id)
    {
        JO::setNullableAdditional($id);
        $jo=JO::show($id);
        $sql="
        SELECT
        manifests.id,
        manifests.code,
        manifests.is_container,
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
      $data['receipt_detail']=JobOrderReceiver::where('header_id', $id)->get();
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

    /*
      Date : 21-04-2021
      Description : Menampilkan cost
      Developer : Didin
      Status : Create
    */
    public static function showCost($id) {
        $dt = JOC::query(['header_id' => $id])->get();
        $data['message'] = 'OK';
        $data['data'] = $dt;

        return response()->json($data);
    } 

    /*
      Date : 29-08-2020
      Description : Menampilkan daftar kpi status berdasarkan job order
      Developer : Didin
      Status : Create
    */
    public function showKpiStatus($id) {
        $data = DB::table('kpi_statuses')
        ->join('job_orders', 'job_orders.service_id', 'kpi_statuses.service_id')
        ->where('job_orders.id', $id)
        ->orderBy('kpi_statuses.sort_number','asc')
        ->select('kpi_statuses.id', 'kpi_statuses.name', 'kpi_statuses.is_done')
        ->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan detail kpi status dari job order
      Developer : Didin
      Status : Create
    */
    public function showKpiStatusData($id) {
        $data = DB::table('kpi_statuses')
        ->join('job_orders', 'job_orders.kpi_id', 'kpi_statuses.id')
        ->where('job_orders.id', $id)
        ->select('kpi_statuses.id', 'kpi_statuses.name', 'kpi_statuses.status', 'kpi_statuses.is_done')
        ->first();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function showDetail($id) {
        $detail = JOD::indexWithStock($id);

        return Response::json($detail, 200, [], JSON_NUMERIC_CHECK);
    }

    public function showTransits($id) {
        $detail = DB::table('job_order_transits')
        ->whereHeaderId($id)
        ->get();


        return Response::json($detail, 200, [], JSON_NUMERIC_CHECK);
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
            'agent_name' => $request->agent_name,
            'awb_number' => $request->awb_number,
            'flight_code' => $request->flight_code,
            'flight_route' => $request->flight_route,
            'flight_date' => dateDB($request->flight_date),
            'cargo_ready_date' => dateDB($request->cargo_ready_date),
            'house_awb' => $request->house_awb,
            'hs_code' => $request->hs_code
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

        JO::storeAdditional($request->additional, $id);
        DB::commit();

        return Response::json(null);
    }

    /*
      Date : 25-03-2021
      Description : Hapus data
      Developer : Didin
      Created By : Fajar
      Status : Edit
    */
    public function destroy($id)
    {
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            JO::destroy($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
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
      $this->using_qty = \App\Http\Controllers\Setting\SettingController::fetch('work_order', 'using_qty');
      if(($this->using_qty->value ?? 0) == 1) {

          if ($wod->qty_leftover < $request->qty) {
            return Response::json(['message' => 'jumlah Armada yang anda input melebihi jumlah Armada pada Work Order!'],500);
          }
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
        $this->manifest_cost($m->id, $jo->route_id);
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
        DB::beginTransaction();
        try {
            $jo=JobOrder::find($id);
            $workOrderDetail = DB::table('work_order_details')
            ->whereId($jo->work_order_detail_id ?? null)
            ->select('price_list_id', 'quotation_detail_id')
            ->first();
            $priceList = DB::table('price_lists')
            ->whereId($workOrderDetail->price_list_id ?? null)
            ->first();
            $quotationDetail=QuotationDetail::find($workOrderDetail->quotation_detail_id ?? null);
            if ($request->is_edit) {
                $jod=JobOrderDetail::find($request->detail_id);
                $request->validate([
                    'piece_id' => 'required',
                    'item_name' => 'required',
                    'total_item' => 'required|integer|min:'.$jod->transported
                    ],[
                    'total_item.min' => 'Minimal barang adalah '.$jod->transported.' karena sebagian sudah terangkut'
                ]);
                $jod->update([
                    'qty' => $request->total_item,
                    'imposition' => $request->imposition,
                    'load_date' => dateDB($request->load_date),
                    'long' => $request->long ?? 0,
                    'wide' => $request->wide ?? 0,
                    'high' => $request->high ?? 0,
                    'volume' => $request->total_volume ?? 0,
                    'weight' => $request->total_tonase ?? 0,
                    'item_name' => $request->item_name,
                    'no_reff' => $request->reff_no,
                    'no_manifest' => $request->manifest_no,
                    'piece_id' => $request->piece_id,
                    'description' => $request->description,
                    'warehouse_receipt_detail_id' => $request->warehouse_receipt_detail_id,
                    'rack_id' => $request->rack_id,
                    'item_id' => $request->item_id,
                    'leftover' => ($jod->leftover+$request->total_item-$jod->qty),
                    'weight_type' => $request->weight_type
                ]);
                JO::adjustVolumetricWeight($id);
                $this->storeShipmentStatus($request->warehouse_receipt_detail_id);


        if ($jo->service_type_id==1) {
          if (empty($jo->quotation_id)) {
            if (empty($priceList)) {
              return Response::json(['message' => 'Tarif Umum dengan trayek dan armada ini tidak ditemukan'],500);
            }

            if($priceList->min_type == 1) {
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
            }
            else {
              $priceListMinDetail = DB::table('price_list_minimum_details')->where('price_list_id', $priceList->id);

              if ($request->imposition==1) {
                $priceListMinDetail = $priceListMinDetail->orderBy('min_m3')->get();
                foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                  if(count($priceListMinDetail) == 1)
                  {
                    $price=$valuePriceMin->price_per_m3;
                    $total_price=$price*$request->total_volume;
                    break;
                  }
                  else
                  {
                    if($keyPriceMin == 0)
                    {
                      if($request->total_volume < $valuePriceMin->min_m3)
                      {
                        $price=$valuePriceMin->price_per_m3;
                        $total_price=$price*$request->total_volume;
                        break;
                      }
                      elseif($request->total_volume >= $valuePriceMin->min_m3 && $request->total_volume < $priceListMinDetail[$keyPriceMin + 1]->min_m3)
                      {
                        $price=$valuePriceMin->price_per_m3;
                        $total_price=$price*$request->total_volume;
                        break;
                      }
                    }
                    elseif($keyPriceMin == count($priceListMinDetail)-1)
                    {
                      $price=$valuePriceMin->price_per_m3;
                      $total_price=$price*$request->total_volume;
                      break;
                    }
                    else {
                      if($request->total_volume >= $valuePriceMin->min_m3 && $request->total_volume < $priceListMinDetail[$keyPriceMin + 1]->min_m3)
                      {
                        $price=$valuePriceMin->price_per_m3;
                        $total_price=$price*$request->total_volume;
                        break;
                      }
                    }
                  }
                }
              } elseif ($request->imposition==2) {
                $priceListMinDetail = $priceListMinDetail->orderBy('min_kg')->get();
                foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                  if(count($priceListMinDetail) == 1)
                  {
                    $price=$valuePriceMin->price_per_kg;
                    $total_price=$price*$request->total_tonase;
                    break;
                  }
                  else
                  {
                    if($keyPriceMin == 0)
                    {
                      if($request->total_tonase < $valuePriceMin->min_kg)
                      {
                        $price=$valuePriceMin->price_per_kg;
                        $total_price=$price*$request->total_tonase;
                        break;
                      }
                      elseif($request->total_tonase >= $valuePriceMin->min_kg && $request->total_tonase < $priceListMinDetail[$keyPriceMin + 1]->min_kg)
                      {
                        $price=$valuePriceMin->price_per_kg;
                        $total_price=$price*$request->total_tonase;
                        break;
                      }
                    }
                    elseif($keyPriceMin == count($priceListMinDetail)-1)
                    {
                      $price=$valuePriceMin->price_per_kg;
                      $total_price=$price*$request->total_tonase;
                      break;
                    }
                    else {
                      if($request->total_tonase >= $valuePriceMin->min_kg && $request->total_tonase < $priceListMinDetail[$keyPriceMin + 1]->min_kg)
                      {
                        $price=$valuePriceMin->price_per_kg;
                        $total_price=$price*$request->total_tonase;
                        break;
                      }
                    }
                  }
                }
              } else {
                $priceListMinDetail = $priceListMinDetail->orderBy('min_item')->get();
                foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                  if(count($priceListMinDetail) == 1)
                  {
                    $price=$valuePriceMin->price_per_item;
                    $total_price=$price*$request->total_item;
                    break;
                  }
                  else
                  {
                    if($keyPriceMin == 0)
                    {
                      if($request->total_item < $valuePriceMin->min_item)
                      {
                        $price=$valuePriceMin->price_per_item;
                        $total_price=$price*$request->total_item;
                        break;
                      }
                      elseif($request->total_item >= $valuePriceMin->min_item && $request->total_item < $priceListMinDetail[$keyPriceMin + 1]->min_item)
                      {
                        $price=$valuePriceMin->price_per_item;
                        $total_price=$price*$request->total_item;
                        break;
                      }
                    }
                    elseif($keyPriceMin == count($priceListMinDetail)-1)
                    {
                      $price=$valuePriceMin->price_per_item;
                      $total_price=$price*$request->total_item;
                      break;
                    }
                    else {
                      if($request->total_item >= $valuePriceMin->min_item && $request->total_item < $priceListMinDetail[$keyPriceMin + 1]->min_item)
                      {
                        $price=$valuePriceMin->price_per_item;
                        $total_price=$price*$request->total_item;
                        break;
                      }
                    }
                  }
                }
              }
            }

            // $total_price=*$price;
          } else {
            $quotationDetail=QuotationDetail::find($jo->quotation_detail_id);

            if($quotationDetail->min_type == 1)
            {
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
            else
            {
              $priceListMinDetail = DB::table('price_list_minimum_details')->where('quotation_detail_id', $quotationDetail->id);

              if ($request->imposition==1) {
                $priceListMinDetail = $priceListMinDetail->orderBy('min_m3')->get();
                foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                  if(count($priceListMinDetail) == 1)
                  {
                    $price=$valuePriceMin->price_per_m3;
                    $total_price=$price*$request->total_volume;
                    break;
                  }
                  else
                  {
                    if($keyPriceMin == 0)
                    {
                      if($request->total_volume < $valuePriceMin->min_m3)
                      {
                        $price=$valuePriceMin->price_per_m3;
                        $total_price=$price*$request->total_volume;
                        break;
                      }
                      elseif($request->total_volume >= $valuePriceMin->min_m3 && $request->total_volume < $priceListMinDetail[$keyPriceMin + 1]->min_m3)
                      {
                        $price=$valuePriceMin->price_per_m3;
                        $total_price=$price*$request->total_volume;
                        break;
                      }
                    }
                    elseif($keyPriceMin == count($priceListMinDetail)-1)
                    {
                      $price=$valuePriceMin->price_per_m3;
                      $total_price=$price*$request->total_volume;
                      break;
                    }
                    else {
                      if($request->total_volume >= $valuePriceMin->min_m3 && $request->total_volume < $priceListMinDetail[$keyPriceMin + 1]->min_m3)
                      {
                        $price=$valuePriceMin->price_per_m3;
                        $total_price=$price*$request->total_volume;
                        break;
                      }
                    }
                  }
                }
              } elseif ($request->imposition==2) {
                $priceListMinDetail = $priceListMinDetail->orderBy('min_kg')->get();
                foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                  if(count($priceListMinDetail) == 1)
                  {
                    $price=$valuePriceMin->price_per_kg;
                    $total_price=$price*$request->total_tonase;
                    break;
                  }
                  else
                  {
                    if($keyPriceMin == 0)
                    {
                      if($request->total_tonase < $valuePriceMin->min_kg)
                      {
                        $price=$valuePriceMin->price_per_kg;
                        $total_price=$price*$request->total_tonase;
                        break;
                      }
                      elseif($request->total_tonase >= $valuePriceMin->min_kg && $request->total_tonase < $priceListMinDetail[$keyPriceMin + 1]->min_kg)
                      {
                        $price=$valuePriceMin->price_per_kg;
                        $total_price=$price*$request->total_tonase;
                        break;
                      }
                    }
                    elseif($keyPriceMin == count($priceListMinDetail)-1)
                    {
                      $price=$valuePriceMin->price_per_kg;
                      $total_price=$price*$request->total_tonase;
                      break;
                    }
                    else {
                      if($request->total_tonase >= $valuePriceMin->min_kg && $request->total_tonase < $priceListMinDetail[$keyPriceMin + 1]->min_kg)
                      {
                        $price=$valuePriceMin->price_per_kg;
                        $total_price=$price*$request->total_tonase;
                        break;
                      }
                    }
                  }
                }
              } else {
                $priceListMinDetail = $priceListMinDetail->orderBy('min_item')->get();
                foreach($priceListMinDetail as $keyPriceMin => $valuePriceMin) {
                  if(count($priceListMinDetail) == 1)
                  {
                    $price=$valuePriceMin->price_per_item;
                    $total_price=$price*$request->total_item;
                    break;
                  }
                  else
                  {
                    if($keyPriceMin == 0)
                    {
                      if($request->total_item < $valuePriceMin->min_item)
                      {
                        $price=$valuePriceMin->price_per_item;
                        $total_price=$price*$request->total_item;
                        break;
                      }
                      elseif($request->total_item >= $valuePriceMin->min_item && $request->total_item < $priceListMinDetail[$keyPriceMin + 1]->min_item)
                      {
                        $price=$valuePriceMin->price_per_item;
                        $total_price=$price*$request->total_item;
                        break;
                      }
                    }
                    elseif($keyPriceMin == count($priceListMinDetail)-1)
                    {
                      $price=$valuePriceMin->price_per_item;
                      $total_price=$price*$request->total_item;
                      break;
                    }
                    else {
                      if($request->total_item >= $valuePriceMin->min_item && $request->total_item < $priceListMinDetail[$keyPriceMin + 1]->min_item)
                      {
                        $price=$valuePriceMin->price_per_item;
                        $total_price=$price*$request->total_item;
                        break;
                      }
                    }
                  }
                }
              }
            }
          }
          JobOrderDetail::find($request->detail_id)->update([
            'price' => $price,
            'total_price' => $total_price,
          ]);
        }
        $workOrder = new \App\Http\Controllers\Marketing\WorkOrderController();
        $workOrder->storeItemPacketPrice($jo->work_order_id);
        JO::adjustVolumetricWeight($id);
        JO::setSize($jo->id);
        JO::countPrice($jo->id);
        JOD::setContainerImposition();
        JOD::resetRequestOutbound($jo->id);
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
        } else {
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

        if ($priceList != null) {
          if ($jo->service_type_id==2) {
            $wrAdd=" and service_type_id = 2 and container_type_id = $jo->container_type_id";
          } else {
            $wrAdd=" and service_type_id = 3 and vehicle_type_id = $jo->vehicle_type_id";
          }
          if (empty($priceList)) {
            return Response::json(['message' => 'Mohon Maaf, Tarif Umum dengan trayek dan armada ini tidak ditemukan. Silahkan Setting di master'],500);
          }
          $price=$priceList->price_full;
          $total_price=$jo->total_unit*$priceList->price_full;
        } else {
          $price=$quotationDetail->price_contract_full ?? 0;
          $total_price=$jo->total_unit*$price;
        }
      }

      JobOrderDetail::create([
        // 'piece_id' => $value['piece_id'],
        'header_id' => $jo->id,
        'load_date' => dateDB($request->load_date),
        'quotation_id' => $jo->quotation_id,
        'quotation_detail_id' => $jo->quotation_detail_id,
        'commodity_id' => $jo->commodity_id,
        'sender_id' => $jo->sender_id,
        'receiver_id' => $jo->receiver_id,
        'create_by' => auth()->id(),
        'is_contract' => ($jo->quotation_id?1:0),
        'piece_id' => $request->piece_id,
        'price' => (!in_array($jo->service_type_id,[2,3])?$price:0) ?? 0,
        'total_price' => (!in_array($jo->service_type_id,[2,3])?$total_price:0) ?? 0,
        'qty' => $request->total_item,
        'long' => $request->long ?? 0,
          'wide' => $request->wide ?? 0,
          'high' => $request->high ?? 0,
        'volume' => $request->total_volume ?? 0,
        'weight' => $request->total_tonase ?? 0,
        'item_name' => $request->item_name,
        'barcode' => $request->barcode ?? "",
        'imposition' => $request->imposition,
        'description' => $request->description,
        'leftover' => $request->total_item,
        'weight_type' => $request->weight_type,
        'warehouse_receipt_detail_id' => $request->warehouse_receipt_detail_id,
          'rack_id' => $request->rack_id,
          'item_id' => $request->item_id
      ]);
      JO::setReceiveDate($jo->id);
      $this->storeShipmentStatus($request->warehouse_receipt_detail_id);
      $jo->update([
        'total_item' => DB::raw("total_item+1")
      ]);
            JO::setSize($jo->id);
            if($jo->work_order_id) {
                $workOrder = new \App\Http\Controllers\Marketing\WorkOrderController();
                $workOrder->storeItemPacketPrice($jo->work_order_id);
            }
            JOD::setContainerImposition();
            JO::countPrice($jo->id);
            JOD::resetRequestOutbound($jo->id);
            DB::commit();
            return Response::json(null);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' =>  $e->getMessage()], 421);
        }
      
    }

    public function edit_cost($id)
    {
      $item= JOC::show($id);
      return Response::json($item,200,[],JSON_NUMERIC_CHECK);
    }

    public function add_cost(Request $request, $id)
    {
      $request->validate([
        'cost_type_id' => 'required',
        'vendor_id' => 'required',
      ]);
      $request->qty = $request->qty ?? 0;
      DB::beginTransaction();
      $jo=JobOrder::find($id);
      $ctt=CostType::find($request->cost_type_id);
      $slug=str_random(6);
      $request->price = JOC::countPrice($request->cost_type_id, $id, $request->price ?? 0);
      $request->total_price = JOC::countTotalPrice($request->cost_type_id, $id, $request->price ?? 0, $request->qty);
      if ($request->is_edit) {
        JobOrderCost::find($request->id)->update([
          'cost_type_id' => $request->cost_type_id,
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
        JOC::storeRequestedDistance($request->id);
      } else {
        $jc=JobOrderCost::create([
          'header_id' => $id,
          'cost_type_id' => $request->cost_type_id,
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
        JOC::storeVendorJob($jc->id);
        JOC::storeRequestedDistance($jc->id);
      }
      JOC::validasiLimitHutang($jc->id);
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

      return Response::json(null);
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
      $request->validate([
        'qty' => 'required',
      ]);
      DB::beginTransaction();
      JobOrderDetail::find($id)->update([
        'qty' => $request->qty
      ]);
      DB::commit();

      return Response::json(null);
    }

    /*
      Date : 16-03-2020
      Description : Menambah KPI Status pada job order
      Developer : Didin
      Status : Edit
    */
    public function add_status(Request $request, $id)
    {
      $request->validate([
        'update_date' => 'required',
        'update_time' => 'required',
        'kpi_status_id' => 'required',
      ]);
      DB::beginTransaction();

      try {
          $jo=JobOrder::find($id);
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
          $workOrder = new \App\Http\Controllers\Marketing\WorkOrderController();
          $workOrder->storeItemPacketPrice($jo->work_order_id);
          if(($request->decrease == 1 && $ks->is_done == 1) || $ks->is_done != 1) {
              JO::decreaseStock($id);
          }
          WR::bill($id);
          DB::commit();
      } catch(Exception $e) {
          return Response::json(['message' => $e->getMessage()], 421);
      }

      return Response::json(null);
    }

    /*
      Date : 16-03-2020
      Description : Menambah KPI Status pada job order secara otomatis
      Developer : Didin
      Status : Edit
    */
    public function autoAddStatus($id)
    {
      JO::autoAddStatus($id);

      return Response::json(null);
    }

    public function show_document($id)
    {
      $data['detail']=JobOrderDocument::where('header_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
    public function show_status($id)
    {
      $jo=JobOrder::find($id);
      $data['kpi_status']=KpiStatus::where('service_id', $jo->service_id)->orderBy('sort_number','asc')->select('id','name')->get();
      $data['detail']=KpiLog::with('kpi_status','creates')->where('job_order_id', $id)->orderBy('created_at','desc')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function update_status(Request $request)
    {
      $request->validate([
        'id' => 'required',
        'update_date' => 'required',
        'update_time' => 'required',
        'kpi_status_id' => 'required',
      ]);
      DB::beginTransaction();
      $kl=KpiLog::with('kpi_status')->find($request->id);
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

      $jo = JobOrder::find($kl->job_order_id);
      $workOrder = new \App\Http\Controllers\Marketing\WorkOrderController();
      $workOrder->storeItemPacketPrice($jo->work_order_id);
      WR::bill($jo->id);
      DB::commit();
    }

    public function delete_status($id)
    {
      DB::beginTransaction();
      KpiLog::find($id)->delete();
      DB::commit();
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
        $msg = "Data successfully updated";
        $status_code = 200;
        DB::beginTransaction();
        try {

            $joc = DB::table('job_order_costs')->whereId($request->id);
            $joc->update([
                'status' => 8
            ]);
            $joc = DB::table('job_order_costs')->whereId($request->id)->first();
            $job_order_id = $joc->header_id;
            $this->cost_journal(new Request(['id' => $job_order_id]));
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $msg  = $e->getMessage();
            $status_code = 421;
        }

        $data['message'] = $msg;
        return response()->json($data, $status_code);

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
        $request->validate([
            'vessel_id' => 'required',
            'voyage' => 'required',
            'pol_id' => 'required',
            'pod_id' => 'required',
            'etd_date' => 'required',
            'eta_date' => 'required',
            'etd_time' => 'required',
            'eta_time' => 'required',
            // 'voyage_schedule_id' => 'required',
            'container_no' => 'required',
            'container_type_id' => 'required',
            'commodity_id' => 'required',
            'commodity' => 'required',
        ]);

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

    /*
      Date : 14-03-2021
      Description : Hapus item job order
      Developer : Didin
      Status : Create
    */
    public function delete_item($id)
    {
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            JOD::destroy($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    public function delete_cost($id)
    {
        DB::beginTransaction();
        JobOrderCost::find($id)->delete();
        DB::commit();
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
    }

    public function store_archive(Request $request)
    {
        DB::beginTransaction();
        foreach ($request->detail as $key => $value) {
            if ($value['value']==1) {
                JobOrder::find($key)->update([
                    'is_operational_done' => 1
                    ]);
                }
            }
            DB::commit();

            return Response::json(null);
    }

    public function delete_armada(Request $request, $id)
    {
        DB::beginTransaction();
        $m=Manifest::find($id);
        $detail=ManifestDetail::where('header_id', $id)->get();
        $jod=ManifestDetail::where('manifest_details.header_id', $id)->leftJoin('job_order_details','job_order_details.id','=','manifest_details.job_order_detail_id')->groupBy('job_order_details.header_id')->pluck('job_order_details.header_id');
        DB::delete("DELETE FROM job_order_costs WHERE manifest_cost_id in (SELECT * FROM (SELECT id FROM manifest_costs WHERE header_id = {$id}) Y)");
        foreach ($detail as $key => $value) {
            JobOrderDetail::where('id',$value->job_order_detail_id)->update([
                'leftover' => DB::raw("leftover+$value->transported"),
                'transported' => DB::raw("transported-$value->transported"),
                ]);
            }
            foreach ($jod as $key => $value) {
                // dd($value);
                if (!$value) {
                    continue;
                }
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

    /*
      Date : 07-04-2020
      Description : Menyalin biaya trayek ke biaya packing list
      Developer : Didin
      Status : Create
    */
    public function manifest_cost($manifest_id, $route_id) {
        // if($route_id != null) {
        //     $cost_details = DB::table('route_cost_details')
        //     ->whereRaw("header_id IN (SELECT id FROM route_costs WHERE route_id = $route_id)")
        //     ->get();
        //     $manifest = DB::table('manifests')
        //     ->whereId($manifest_id)
        //     ->first();

        //     foreach ($cost_details as $detail) {
        //         $cost_type = DB::table('cost_types')
        //         ->whereId($detail->cost_type_id)
        //         ->first();
        //         DB::table('manifest_costs')
        //         ->insert([
        //             'header_id' => $manifest_id,
        //             'company_id' => $manifest->company_id,
        //             'cost_type_id' => $detail->cost_type_id,
        //             'vendor_id' => $cost_type->vendor_id ?? null,
        //             'transaction_type_id' => 21,
        //             'qty' => 1,
        //             'price' => $detail->cost,
        //             'total_price' => $detail->cost,
        //             'total_price' => $detail->cost,
        //             'is_internal' => $detail->is_internal,
        //             'type' => 1,
        //             'create_by' => auth()->id(),
        //             'is_edit' => 1
        //         ]);
        //     }
        // }
    }

    public function cost_journal(Request $request)
    {
        DB::beginTransaction();
        $jo=DB::table('job_orders')->where('id', $request->id)->first();
        $cst = DB::table('job_order_costs')
        ->leftJoin('cost_types','cost_types.id','job_order_costs.cost_type_id')
        ->where('header_id', $request->id)
        ->where('status', 8)
        ->where('cost_types.type', 1)
        ->select('job_order_costs.id')
        ->get();
        $cst->each(function($v){
            DB::table('job_order_costs')
            ->whereId($v->id)
            ->update([
                "status" => JOC::getPostedStatus()
            ]);
        });

      // COST CASH
      $cst_cash=DB::table('job_order_costs')
      ->leftJoin('cost_types','cost_types.id','job_order_costs.cost_type_id')
      ->where('header_id', $request->id)
      ->where('status', 8)
      ->where('cost_types.type', 2)
      ->selectRaw('
        job_order_costs.id,
        job_order_costs.status,
        job_order_costs.cost_type_id,
        job_order_costs.vendor_id,
        job_order_costs.total_price,
        job_order_costs.created_at,
        job_order_costs.description,
        cost_types.name,
        cost_types.type,
        cost_types.akun_biaya,
        cost_types.akun_kas_hutang,
        cost_types.akun_uang_muka
      ')
      ->get();

      if(count($cst) == 0 && count($cst_cash) == 0) {

           return Response::json(['message' => 'Tidak ada biaya job order yang disetujui atasan!'],500,[],JSON_NUMERIC_CHECK);
      }
      foreach ($cst_cash as $value) {

          $code = new TransactionCode($jo->company_id, 'jobOrderCost');
          $tp = DB::table('type_transactions')->where('slug', 'jobOrderCost')->first();

          $account = DB::table('accounts')
          ->whereId($value->akun_kas_hutang)
          ->first();
          if($account->no_cash_bank != 0) {
              $i=CashTransaction::create([
                    'company_id' => $jo->company_id,
                    'type_transaction_id' => $tp->id,
                    'code' => $jo->code,
                    'reff' => $jo->code,
                    'jenis' => 2,
                    'type' => $account->no_cash_bank,
                    'relation_id' => $value->id,
                    'description' => $value->description,
                    'total' => $value->total_price,
                    'account_id' => $value->akun_kas_hutang,
                    'date_transaction' => dateDB($value->created_at),
                    'status_cost' => 1,
                    'created_by' => auth()->id()
                ]);

              CashTransactionDetail::create([
                  'header_id' => $i->id,
                  'account_id' => $value->akun_biaya,
                  'amount' => $value->total_price,
                  'job_order_cost_id' => $value->id,
                  'description' => @$value->description,
                  'jenis' => 1
              ]);
          } else {
              $jurnal=[
                  'company_id' => $jo->company_id,
                  'date_transaction' => date('Y-m-d'),
                  'created_by' => auth()->id(),
                  'code' => $jo->code,
                  'description' => $jo->description,
                  'debet' => 0,
                  'credit' => 0,
                  'status' => 2,
                  'type_transaction_id' => $tp->id
              ];

              $j = Journal::create($jurnal);

            JournalDetail::create([
                'header_id' => $j->id,
                'account_id' => $value->akun_biaya,
                'debet' => $value->total_price,
                'credit' => 0,
                'description' => $value->description,
            ]);
            if (!$value->akun_uang_muka) return response()->json(['message' => "Tidak ada akun uang muka / ayat silang yang disetting pada biaya {$value->name}"],500);
            JournalDetail::create([
                'header_id' => $j->id,
                'account_id' => $value->akun_uang_muka,
                'debet' => 0,
                'credit' => $value->total_price,
                'description' => @$value->description
            ]);
          }

          $joc = JobOrderCost::find($value->id);
            $joc->update([
              'status' => 5,
              'journal_id' => $j->id ?? null
            ]);
      }

      DB::commit();

      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }

    public function cancel_cost_journal($cost_id)
    {
      DB::beginTransaction();
      $joc = DB::table('job_order_costs')->where('id', $cost_id)->first();
      if ($joc->journal_id) {
        DB::delete("DELETE p, pd FROM payables as p LEFT JOIN payable_details as pd ON pd.header_id = p.id WHERE p.journal_id = {$joc->journal_id}");
        DB::table('job_order_costs')
        ->whereId($cost_id)
        ->update([
            'journal_id' => null
        ]);
        DB::delete("DELETE j FROM journals as j WHERE j.id = {$joc->journal_id}");
      }
      DB::delete("DELETE ct, ctd FROM cash_transactions as ct LEFT JOIN cash_transaction_details as ctd ON ctd.header_id = ct.id WHERE ct.relation_id = {$cost_id}");
      DB::update("UPDATE job_order_costs SET journal_id = null, status = 1 WHERE id = {$cost_id}");
      DB::commit();

      return response()->json(['message' => 'OK']);
    }

    public function submit_armada_lcl(Request $request,$id)
    {
        DB::beginTransaction();
        $jo=DB::table('job_orders')->where('id', $id)->first();
        $qcost=DB::table('quotation_costs')->where('quotation_detail_id', $jo->quotation_detail_id)->orderBy('id')->get();
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
        $manifest_id = $m->id;
        M::storeAdditional($request->additional, $manifest_id);
        // $this->manifest_cost($m->id, $jo->route_id);
        $diangkut = 0;
        $detail = [];
        foreach ($request->detail as $value) {
            $v=(object)$value;
            ManifestDetail::create([
                'header_id' => $m->id,
                'job_order_detail_id' => $v->detail_id,
                'create_by' => auth()->id(),
                'update_by' => auth()->id(),
                'requested_qty' => $v->angkut,
                'transported' => $v->angkut,
                'leftover' => 0,
            ]);
            JobOrderDetail::find($v->detail_id)->update([
                'transported' => DB::raw("transported+{$v->angkut}"),
                'leftover' => ($v->leftover-$v->angkut)
                ]);
                $diangkut+=$v->angkut;
            }
            foreach ($qcost as $value) {
                $uuid = DB::table('manifest_costs')->insertGetId([
                    'header_id' => $m->id,
                    'cost_type_id' => $value->cost_type_id,
                    'transaction_type_id' => 21,
                    'vendor_id' => $value->vendor_id,
                    'qty' => $value->total,
                    'price' => $value->cost,
                    'total_price' => $value->total_cost,
                    'description' => $value->description,
                    'type' => 1,
                    'is_edit' => 1,
                    'quotation_costs' => $value->total_cost,
                    'create_by' => auth()->id()
                ]);
            }
            DB::commit();
            HitungJoCostManifestJob::dispatch($m->id);
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

        $dt = DB::table('job_orders')
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
        $additionalJo = json_decode($dt->additional);
        $data['item'] = $dt;
        $additional = AdditionalField::indexByTransaction('jobOrder');
        $plain = [];
        foreach ($additionalJo as $i => $v) {
            $name = '';
            foreach ($additional as $a) {
                if($a->slug == $i) {
                    $name = $a->name;
                }
            }
            if($name) {
                $plain[$name] = $v;
            }
        }
        $dt->additional = json_decode(json_encode($plain)); 


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
        return view('operational.job_order.print',$data);
    }
    public function jo_margin_detail($id) {
        $item = DB::table('job_orders as jo');
        $item = $item->leftJoin('contacts as c','c.id','jo.customer_id');
        $item = $item->leftJoin('service_types as st','st.id','jo.service_type_id');
        $item = $item->leftJoin('services as s','s.id','jo.service_id');
        $item = $item->leftJoin('routes as r','r.id','jo.route_id');
        $item = $item->where('jo.id', $id);
        $item = $item->selectRaw('
        jo.code,
        jo.shipment_date,
        jo.total_price,
        st.name as service_type,
        s.name as service,
        c.name as customer,
        r.name as trayek
        ');
        $item = $item->first();

        $dt = DB::table('job_order_costs as joc');
        $dt = $dt->leftJoin('cost_types as ct','ct.id','joc.cost_type_id');
        $dt = $dt->where('joc.header_id', $id);
        $dt = $dt->selectRaw('joc.*, ct.name')->get();

        $in = DB::table('invoice_details as ids');
        $in = $in->leftJoin('invoices as ins','ins.id','ids.header_id');
        $in = $in->where('ids.job_order_id', $id);
        $in = $in->where('ids.is_other_cost', 0);
        $in = $in->selectRaw('ins.code,ins.date_invoice,ids.total_price,ids.description,ids.ppn')->get();

        return response()->json([
            'item' => $item,
            'details' => $dt,
            'invoice' => $in
        ]);
    }

    public function view_jo_margin($id){
      $item = DB::table('job_orders as jo');
      $item = $item->leftJoin('work_order_details as wod','wod.id','jo.work_order_detail_id');
      $item = $item->leftJoin('price_lists as pl','pl.id','wod.price_list_id');
      $item = $item->leftJoin('quotation_details as qd','qd.id','wod.quotation_detail_id');
      $item = $item->leftJoin('services as s','s.id','jo.service_id');
      $item = $item->where('jo.id', $id);
      $item = $item->selectRaw('
        jo.code,
        ifnull((qd.price_contract_full*qd.idr_value),(pl.idr_value*pl.price_full)) as price,
        wod.qty as total_unit,
        s.name as service
      ');
      $item = $item->first();

      $dt = DB::table('job_order_costs as joc');
      $dt = $dt->leftJoin('cost_types as ct','ct.id','joc.cost_type_id');
      $dt = $dt->where('joc.header_id', $id);
      $dt = $dt->where('joc.type', 1);
      $dt = $dt->whereIn('joc.status', [3,5]);
      $dt = $dt->selectRaw('joc.*, ct.name')->get();

      return response()->json([
        'item' => $item,
        'details' => $dt,
      ],200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 16-03-2021
      Description : Download import item
      Developer : Didin
      Status : Create
    */
    public function downloadImportItem() {
        try {
            return JOD::downloadImportItemExample();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
            $data['message'] = $msg;
            return response()->json($data, $status_code);
        }

    }
    /*

      Date : 16-03-2021
      Description : Download import item
      Developer : Didin
      Status : Create
    */
    public function importItemWarehouse(Request $request) {
        $status_code = 200;
        $msg = 'Data successfully imported';
        DB::beginTransaction();
        try {
            $dt = JOD::importItemWarehouse($request->file('file'), $request->warehouse_id);
            $data['data'] = $dt;
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }
}
