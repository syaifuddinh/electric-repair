<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Quotation;
use App\Model\QuotationDetail;
use App\Model\QuotationCost;
use App\Model\Contact;
use App\Utils\TransactionCode;
use App\Model\Route as Trayek;
use App\Model\Company;
use App\Model\VehicleType;
use App\Model\Moda;
use App\Model\Commodity;
use App\Model\Piece;
use App\Model\Service;
use App\Model\PriceList;
use App\Model\CombinedPrice;
use App\Model\ServiceType;
use App\Model\ContainerType;
use App\Model\Rack;
use App\Model\Warehouse;
use App\Abstracts\Marketing\PriceListMinimumDetail;
use App\Model\QuotationItem;
use Response;
use DB;
use Carbon\Carbon;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
      $data['company']=companyAdmin(auth()->id());
    $data['commodity']=Commodity::select('id', 'name')->get();
    $data['service']=Service::with('service_type')->get();
    $data['service_warehouse']= Service::with('service_type')->where('is_warehouse', 1)->get();
    $data['piece']=Piece::select('id', 'name')->get();
    $data['combined_price']=CombinedPrice::select('id')->get();
    $data['moda']=Moda::select('id', 'name')->get();
    $data['commodity']=Commodity::select('id', 'name')->get();
    $data['vehicle_type']=VehicleType::all();
    $data['container_type']=ContainerType::select('id', 'code', 'name', 'size', 'unit')->get();
    $data['route']=Trayek::select('id', 'name')->get();
    $data['rack']=Rack::select('id', 'code', 'name');
    $data['warehouse']=Warehouse::select('id', 'code', 'name')->get();
    $data['service_types']=ServiceType::select('id', 'name')->orderBy('id')->get();
    $data['type_1']=[];
    $data['type_2']=[];
    $data['type_3']=[];
    $data['type_4']=[];
    $data['type_5']=[];
    $data['type_6']=[];
    $data['type_7']=[];
    $data['type_10']=[];
    foreach ($data['service'] as $value) {
        if ($value->service_type->id==1) {
            $data['type_1'][]=$value->id;
        } elseif ($value->service_type->id==2) {
            $data['type_2'][]=$value->id;
        } elseif ($value->service_type->id==3) {
            $data['type_3'][]=$value->id;
        } elseif ($value->service_type->id==4) {
            $data['type_4'][]=$value->id;
        } elseif ($value->service_type->id==5) {
            $data['type_5'][]=$value->id;
        } elseif ($value->service_type->id==6) {
            $data['type_6'][]=$value->id;
        } elseif ($value->service_type->id==7) {
            $data['type_7'][]=$value->id;
        } else {
            $data['type_10'][]=$value->id;
        }
    }

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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data=Quotation::where('id',$id)->with('company','customer','sales', 'quotation_item')->first();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function item($id)
    {
      $data['details']=QuotationDetail::with('service','piece','route','service','vehicle_type','container_type')->where('header_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function itemQuotation($id)
    {
      $data['details'] = QuotationItem::with('item')->where('quotation_id', $id)->get();
      return response()->json($data, 200);
    }

    public function cost($id)
    {
      $data['details']=QuotationDetail::with('commodity','service','route','vehicle_type','container_type','cost_details','cost_details.vendor','cost_details.cost_type')->where('header_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function jo_history($id)
    {
      $data=DB::table('job_orders as jo')
      ->leftJoin('job_order_details as jod','jod.header_id','jo.id')
      ->leftJoin('pieces','pieces.id','jod.piece_id')
      ->leftJoin('services','services.id','jo.service_id')
      ->where('jo.quotation_id', $id)
      ->selectRaw('
      jo.id,
      jo.code,
      jo.shipment_date,
      jo.service_type_id,
      services.name as service,
      max(jo.total_price) as tarif,
      concat(max(jo.total_unit)," Unit") as qty_unit,
      group_concat(concat(jod.qty," ",pieces.name) separator "<br>") as qty_item
      ')->groupBy('jo.id')->orderBy('jo.shipment_date','desc')->orderBy('jo.id','desc')->get();

      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['item']=Quotation::with('company','customer')->where('id', $id)->first();
      $data['editing']=Quotation::where('id', $id)->first()->getOriginal();
      $data['sales']=Contact::where('is_sales', 1)->get();
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
        'date_start_contract' => 'required',
        'date_end_contract' => 'required',
        'sales_commision' => 'required|integer',
      ]);

      DB::beginTransaction();
      Quotation::find($id)->update([
        'sales_id' => $request->sales_id,
        'date_start_contract' => dateDB($request->date_start_contract),
        'date_end_contract' => dateDB($request->date_end_contract),
        'is_active' => $request->is_active,
        'sales_commision' => $request->sales_commision,
        'send_type' => $request->send_type,
        'description_contract' => $request->description_contract,
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function cancel($id)
    {
      DB::beginTransaction();
      Quotation::find($id)->update([
        'is_contract' => 0,
        'is_cancel' => 1,
        'status_approve' => 2,
        'cancel_by' => auth()->id(),
      ]);
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
        //
    }

    public function save_typing(Request $request, $id)
    {
      DB::beginTransaction();
      Quotation::find($id)->update([
        'name' => $request->name,
        'bill_type' => $request->bill_type,
        'send_type' => $request->send_type,
        'date_end_contract' => dateDB($request->date_end_contract),
        'date_start_contract' => dateDB($request->date_start_contract),
        'description_amandemen' => $request->description_amandemen,
      ]);
      DB::commit();
      return Response::json(null);
    }

    public function amandemen($id)
    {
      $data['item']=Quotation::find($id);
      $data['piece']=DB::table('pieces')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_amandemen(Request $request, $id)
    {
      // dd($request);
      // return Response::json($request,500);
      DB::beginTransaction();
      $q=Quotation::find($id);
      if ($request->is_new) {
        $code = new TransactionCode($q->company_id, "contract");
        $code->setCode();
        $trx_code = $code->getCode();
      } else {
        $qq=Quotation::find($q->parent_id);
        $trx_code = $qq->no_contract;
      }
      Quotation::where('id', $q->parent_id)->update([
        'is_active' => 0
      ]);

      $q->update([
        'bill_type' => $request->bill_type,
        'send_type' => $request->send_type,
        'date_end_contract' => dateDB($request->date_end_contract),
        'date_start_contract' => dateDB($request->date_start_contract),
        'description_amandemen' => $request->description_amandemen,
        'date_amandemen' => Carbon::now(),
        'no_contract' => $trx_code,
        'imposition' => $request->imposition,
        'piece_id' => $request->piece_id,
        'price_full_contract' => $request->price_full_contract,
        'is_hide' => 0,
        'is_active' => 1
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function clone_amandemen($id)
    {
      DB::beginTransaction();
      $q=Quotation::find($id);
      // dd($q);
      if ($q->child->id??null) {
        return Response::json(['id' => $q->child->id]);
      }
      $code = new TransactionCode($q->company_id, "contract");
      $code->setCode();
      $trx_code = $code->getCode();

      $qnew=Quotation::create([
        'company_id' => $q->company_id,
        'customer_id' => $q->customer_id,
        'sales_id' => $q->sales_id,
        'customer_stage_id' => $q->customer_stage_id,
        'created_by' => auth()->id(),
        'approve_by' => $q->approve_by,
        'contract_by' => $q->contract_by,
        'code' => $q->code,
        'no_inquery' => $q->no_inquery,
        'date_inquery' => $q->date_inquery,
        'date_approve' => $q->date_approve,
        'no_contract' => $trx_code,
        'imposition' => $q->imposition,
        'piece_id' => $q->piece_id,
        'date_contract' => Carbon::now(),
        'date_start_contract' => $q->date_start_contract,
        'date_end_contract' => $q->date_end_contract,
        'is_contract' => $q->is_contract,
        'sales_commision' => $q->sales_commision,
        'bill_type' => $q->bill_type,
        'send_type' => $q->send_type,
        'price_full_inquery' => $q->price_full_inquery,
        'price_full_contract' => $q->price_full_contract,
        'description_contract' => $q->description_contract,
        'type_entry' => $q->type_entry,
        'name' => $q->name,
        'submit_by' => $q->submit_by,
        'approve_direction_by' => $q->approve_direction_by,
        'approve_manager_by' => $q->approve_manager_by,
        'status_approve' => 4,
        'parent_id' => $id,
        'is_hide' => 1,
        'is_active' => 0
      ]);
      $qd=QuotationDetail::where('header_id', $id)->get()->toArray();
      foreach ($qd as $key => $v) {
        $quotation_id = $qnew->id;
        $params = (array) $v;
        unset($params['id']);
        unset($params['header_id']);
        unset($params['imposition_name']);
        $params['header_id'] = $quotation_id;
        $qdn = QuotationDetail::create($params);
        $v = (object) $v;
        $qc=QuotationCost::where('quotation_detail_id', $v->id)->get();
        PriceListMinimumDetail::cloneByQuotationDetail($v->id, $qdn->id);
        foreach ($qc as $x) {
          QuotationCost::create([
            'header_id' => $qnew->id,
            'cost_type_id' => $x->cost_type_id,
            'vendor_id' => $x->vendor_id,
            'created_by' => $x->created_by,
            'total' => $x->total,
            'cost' => $x->cost,
            'description' => $x->description,
            'is_internal' => $x->is_internal,
            'quotation_detail_id' => $qdn->id,
            'route_cost_id' => $x->route_cost_id,
            'total_cost' => $x->total_cost,
          ]);
        }
      }
      DB::commit();

      return Response::json(['id' => $qnew->id]);
    }

    public function stop_contract(Request $request, $id)
    {
      // dd($request);
      DB::beginTransaction();
      Quotation::find($id)->update([
        'description_stop_contract' => $request->description_stop_contract,
        'stop_contract_by' => auth()->id(),
        'date_stop_contract' => Carbon::now(),
        'is_active' => 0
      ]);
      DB::commit();
    }
}
