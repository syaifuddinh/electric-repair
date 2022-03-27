<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;
use Exception;

class PackagingDetailController extends Controller
{


    public function list($packaging_id)
    {
        $dt = DB::table('packaging_details')
        ->wherePackagingId($packaging_id)
        ->get();

        return $dt;
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $packaging_id)
    {
      $request->validate([
        'warehouse_receipt_detail_id' => 'required',
      ], [
      ]);
      DB::beginTransaction();
      try {
          $params = [];
          $params['packaging_id'] = $packaging_id;
          $params['warehouse_receipt_detail_id'] = $request->warehouse_receipt_detail_id;
          $params['qty'] = $request->qty;
          DB::table('packaging_details')
          ->insert($params);
          DB::commit();
      } catch(Exception $e) {
        DB::rollback();

        return Response::json(['message' => $e->getMessage()], 421);
      }
      
      return Response::json(['message' => 'Data saved successfully']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $jo=JobOrder::with('invoice_detail.invoice','trayek:id,name','moda:id,name','collectible','container_type','vehicle_type','customer','service:id,name,service_type_id','kpi_status:id,name,is_done','sender','receiver', 'service.service_type:id,name','work_order:id,code')
      ->leftJoin('warehouse_receipts', 'warehouse_receipts.id', 'job_orders.warehouse_receipt_id')
      ->where('job_orders.id', $id)
      ->select('job_orders.*', 'warehouse_receipts.code AS warehouse_receipt_code')
      ->first();
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
      ->whereRaw("if(work_order_details.quotation_detail_id is not null,quotation_details.service_type_id,price_lists.service_type_id) = {$jo->service->service_type_id} and work_order_details.id != $jo->work_order_detail_id and work_order_details.header_id = $jo->work_order_id")
      ->selectRaw("work_order_details.id,if(work_order_details.quotation_detail_id is not null,concat(service1.name,' - Rp.',FORMAT(ifnull(quotation_details.price_contract_full,0)+ifnull(quotation_details.price_contract_tonase,0)+ifnull(quotation_details.price_contract_volume,0)+ifnull(quotation_details.price_contract_item,0),2)),concat(service2.name,' - Rp.',format(ifnull(price_lists.price_full,0)+ifnull(price_lists.price_tonase,0)+ifnull(price_lists.price_volume,0)+ifnull(price_lists.price_item,0),2))) as name")
      ->get();
      $data['manifest']=DB::select($sql);

      $data['detail']=JobOrderDetail::where('job_order_details.header_id', $id)
      ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id')
      ->join('warehouse_receipts', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
      ->leftJoin('pieces', 'pieces.id', 'job_order_details.piece_id')
      ->select(
        'job_order_details.id',
        'warehouse_receipt_detail_id',
        'job_order_details.warehouse_receipt_detail_id',
        'job_order_details.rack_id',
        'job_order_details.item_id',
        'job_order_details.item_name',
        'job_order_details.qty',
        'job_order_details.volume',
        'job_order_details.weight',
        'job_order_details.long',
        'job_order_details.wide',
        'job_order_details.high',
        'job_order_details.piece_id',
        'job_order_details.imposition',
        'job_order_details.description',
        'warehouse_receipt_details.header_id AS warehouse_receipt_id', 'job_order_details.piece_id',
        'pieces.name AS piece_name',
        'warehouse_receipts.code AS warehouse_receipt_code',
        DB::raw("(SELECT IFNULL(SUM(qty), 0) FROM warehouse_stock_details WHERE warehouse_receipt_id = warehouse_receipts.id AND item_id = job_order_details.item_id AND rack_id = job_order_details.rack_id) AS stock")
    )
      ->get();
      $data['piece']=Piece::all();
      $data['cost_type']=CostType::with('parent')->where('is_invoice', 0)->where('company_id', $data['item']->company_id)->where('parent_id','!=',null)->get();
      $data['vendor']=Contact::whereRaw("is_vendor = 1 and vendor_status_approve = 2")->select('id','name')->get();
      $data['cost_detail']=JobOrderCost::with('cost_type','vendor')->where('header_id', $id)->get();
      $data['receipt_detail']=JobOrderReceiver::where('header_id', $id)->get();
      $data['kpi_status']=KpiStatus::where('service_id', $data['item']->service_id)->orderBy('sort_number','asc')->select('id','name', 'is_done')->get();
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
      $data['packaging']=Packaging::with('warehouse', 'rack')->where('job_order_id', $id)->first();
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
      $data['item']=JobOrder::with('invoice_detail.invoice','customer','piece','service','service.service_type','trayek','vehicle_type','container_type')->where('id', $id)->first();
      $data['vehicle_type']=VehicleType::select('id','name')->get();
      $data['commodity']=Commodity::select('id','name')->get();
      $data['address']=ContactAddress::whereRaw("contact_id = ".$data['item']->customer_id)->leftJoin('contacts','contacts.id','=','contact_addresses.contact_address_id')->select('contacts.id','contacts.name','contacts.address')->get();
      $data['detail_jasa']=JobOrderDetail::where('header_id', $id)->first();
      $data['staff_gudang']=Contact::whereRaw("is_staff_gudang = 1")->select('id','name','address','company_id')->get();
      $data['warehouse']=Warehouse::where('is_active', 1)->get();
      $data['packaging']=Packaging::where('job_order_id', $id)->first();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
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

        'imposition' => $request->imposition,
        'description' => $request->description,
        'leftover' => $request->total_item,
      ]);

      DB::commit();

      return Response::json(null);
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

      DB::beginTransaction();
      $jo=JobOrder::find($id)->update([
        'shipment_date' => dateDB($request->shipment_date),
        'description' => $request->description,
      ]);

      Packaging::where('job_order_id', $id)->update([
        'warehouse_id' => $request->warehouse_id,
        'staff_gudang_name' => $request->staff_gudang_name,
        'item_name' => $request->item_name,

        'qty' => $request->qty,
        'price' => $request->price,
        'is_overtime' => $request->is_overtime,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function store_item_detail(Request $request, $id)
    {
      $request->validate([
        'piece_id' => 'required',
        'qty' => 'required',
        'long' => 'required',
        'wide' => 'required',
        'height' => 'required',
        'volume' => 'required',
        'weight' => 'required',
        'description' => 'required',
      ]);

      DB::beginTransaction();
      JobOrderDetail::create([
            // 'piece_id' => $value['piece_id'],
          'header_id' => $id,
          'create_by' => auth()->id(),
          'piece_id' => $request->piece_id,
          'qty' => $request->total_item,
          'long' => strval($request->long),
          'wide' => strval($request->wide),
          'height' => strval($request->height),
          'volume' => strval($request->total_volume),
          'weight' => strval($request->total_tonase),
          'description' => $request->description,
      ]);
      DB::commit();

      return Response::json(null);
    }


    public function destroy_item_detail($id)
    {
      DB::beginTransaction();
      JobOrderDetail::find($id)->delete();
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
      Packaging::where('job_order_id', $id)->delete();
      $jo->delete();
      DB::commit();
    }

    public function find_price(Request $request)
    {
      # code...
      $wod = WorkOrderDetail::find($request->work_order_detail_id);
    }
}
