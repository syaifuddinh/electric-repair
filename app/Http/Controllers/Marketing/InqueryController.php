<?php
namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Route as Trayek;
use App\Model\RouteCost;
use App\Model\Company;
use App\Model\Contact;
use App\Model\CustomerStage;
use App\Model\Quotation;
use App\Model\QuotationDetail;
use App\Model\QuotationCost;
use App\Model\QuotationHistoryOffer;
use App\Model\PriceList;
use App\Model\QuotationFile;
use App\Model\Piece;
use App\Model\Commodity;
use App\Model\Service;
use App\Model\ServiceType;
use App\Model\Moda;
use App\Model\ContainerType;
use App\Model\VehicleType;
use App\Model\Rack;
use App\Model\CostType;
use App\Model\Inquery;
use App\Model\Lead;
use App\Model\Warehouse;
use App\Model\CombinedPrice;
use App\Model\Notification;
use App\Model\NotificationUser;
use App\Utils\TransactionCode;
use Response;
use Exception;
use Carbon\Carbon;
use App\Abstracts\Marketing\Quotation AS Q;
use App\Abstracts\Marketing\QuotationDetail AS QD;
use App\Abstracts\Marketing\QuotationFile AS QF;
use App\Abstracts\Marketing\PriceListMinimumDetail;
use App\Abstracts\Contact AS CT;
use App\Model\QuotationItem;
use Illuminate\Support\Facades\DB;

class InqueryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    protected $status=[
        1 => 'Penawaran',
        2 => 'Diajukan',
        3 => 'Disetujui',
        4 => 'Kontrak',
        5 => 'Ditolak'
    ];

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
      $data['company']=companyAdmin(auth()->id());
      $data['piece']=Piece::all();
      $data['customer_stage']=CustomerStage::all();
      $data['negotiation']=CustomerStage::where('is_negotiation', 1)->first();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 08-06-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        // dd($request);
        $request->validate([
            'name' => 'required',
            'bill_type' => 'required',
            'customer_stage_id' => 'required',
            // 'sales_id' => 'required',
            'customer_id' => 'required',
            'send_type' => 'required',
            'date_inquery' => 'required',
            // 'no_inquery' => 'required|unique:quotations',
            'price_full_inquery' => 'required_if:bill_type,2',
            'imposition' => 'required_if:bill_type,2',
            'piece_id' => 'required_if:imposition,3'
        ]);

        try {
            CT::validate($request->customer_id);
        } catch (Exception $e) {
            throw new Exception('Customer not found');
        }

        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            $params = $request->all();
            $params['created_by'] = auth()->id();
            $params['company_id'] = auth()->user()->company_id;
            Q::store($params);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['item'] = Q::show($id);
        $data['details'] = QD::index($id);
        $data['detail_items'] = QuotationItem::with('item')->where('quotation_id', $id)->get()->all();
        $data['cost'] = QuotationCost::with('quotation_detail.service','vendor','cost_type')->where('header_id', $id)->orderBy('quotation_detail_id','asc')->get();
        $data['vendor']=Contact::whereRaw("is_vendor=1 and vendor_status_approve=2")->select('id','name')->get();
        $data['piece']=DB::table('pieces')->select('id','name')->get();
        $data['cost_type']=CostType::with('parent')->where('is_invoice', 0)->where('company_id',$data['item']->company_id)->where('parent_id','!=', null)->get();
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
      $data['company']=companyAdmin(auth()->id());
      $data['customer']=Contact::whereRaw('is_pelanggan=1')->get();
      $data['sales']=Contact::whereRaw('is_sales=1')->get();
      $data['customer_stage']=CustomerStage::all();
      $data['item']=Quotation::find($id);
      if (in_array($data['item']->status_approve,[3,4])) {
        return Response::json(['message' => 'Quotation tidak dapat di edit karena sudah melewati pengajuan!'],500);
      }

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
      $request->validate([
        'name' => 'required',
        'bill_type' => 'required',
        'customer_stage_id' => 'required',
        'sales_id' => 'required',
        'customer_id' => 'required',
        'send_type' => 'required',
        'date_inquery' => 'required',
        // 'no_inquery' => 'required',
        'price_full_inquery' => 'integer'
      ]);
      DB::beginTransaction();
      $con=Contact::find($request->customer_id);
      Quotation::find($id)->update([
        // 'company_id' => $con->company_id,
        'customer_id' => $request->customer_id,
        'sales_id' => $request->sales_id,
        'customer_stage_id' => $request->customer_stage_id,
        'no_inquery' => $request->no_inquery,
        'is_active' => 1,
        'bill_type' => $request->bill_type,
        'send_type' => $request->send_type,
        'price_full_inquery' => ($request->price_full_inquery?:0),
        'date_inquery' => dateDB($request->date_inquery),
        'description_inquery' => $request->description_inquery,
        'name' => $request->name,
        'imposition' => $request->imposition,
        'piece_id' => $request->piece_id,
      ]);
      DB::commit();

      return Response::json(['id' => $id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      DB::beginTransaction();
      Quotation::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function show_detail($id)
    {
      $data['item']=QuotationDetail::where('id',$id)->with('header', 'detail.service.service_type')->first();
      $data['price_list_minimum_detail']=DB::table('price_list_minimum_details')->where('quotation_detail_id', $id)->get();
      $data['company']=companyAdmin(auth()->id());
      $data['commodity']=Commodity::all();
      $data['service']=Service::with('service_type')->get();
      $data['piece']=Piece::all();
      $data['combined_price']=CombinedPrice::select('id')->get();
      $data['moda']=Moda::all();
      $data['commodity']=Commodity::all();
      $data['vehicle_type']=VehicleType::all();
      $data['container_type']=ContainerType::all();
      $data['route']=Trayek::all();
      $data['warehouse']=Warehouse::all();
      $data['type_1']=[];
      $data['type_2']=[];
      $data['type_3']=[];
      $data['type_4']=[];
      $data['type_5']=[];
      $data['type_6']=[];
      $data['type_7']=[];
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
        } else {
          $data['type_7'][]=$value->id;
        }
      }
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_detail(Request $request, $id, $iddetail=null)
    {
        $request->validate([
            'service_id' => 'required_if:price_type,1',
            'route_id' => 'required_if:stype_id,1|required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4',
            'commodity_id' => 'required_if:stype_id,5|required_if:stype_id,1',
            'piece_id' => 'required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7',
            'moda_id' => 'required_if:stype_id,1',
            'vehicle_type_id' => 'required_if:stype_id,3|required_if:stype_id,4',
            "min_imposition" => 'required_if:stype_id,1',
            "price_imposition" => 'required_if:stype_id,1',
            "price_inquery_full" => 'required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7',
            'container_type_id' => 'required_if:stype_id,2',
            'warehouse_id' => 'required_if:stype_id,5',
        ]);
        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            $service=DB::table('services')->where('id', $request->service_id)->first();
            if ($request->imposition==1) {
                $price_volume=$request->price_imposition;
                $price_tonase=null;
                $price_item=null;
                $price_min_volume=$request->min_imposition;
                $price_min_tonase=null;
                $price_min_item=null;
                if ($request->price_list_price_full!=$request->price_imposition) {
                    $is_approve=0;
                }
                $price_volume_default=$request->price_list_price_full;
            } elseif ($request->imposition==2) {
                $price_volume=null;
                $price_tonase=$request->price_imposition;
                $price_item=null;
                $price_min_volume=null;
                $price_min_tonase=$request->min_imposition;
                $price_min_item=null;
                if ($request->price_list_price_full!=$request->price_imposition) {
                    $is_approve=0;
                }
                $price_tonase_default=$request->price_list_price_full;
            } elseif ($request->imposition==3) {
                $price_volume=null;
                $price_tonase=null;
                $price_item=$request->price_imposition;
                $price_min_volume=null;
                $price_min_tonase=null;
                $price_min_item=$request->min_imposition;
                if ($request->price_list_price_full!=$request->price_imposition) {
                    $is_approve=0;
                }
                $price_item_default=$request->price_list_price_full;
            } else {
                $price_volume=null;
                $price_tonase=null;
                $price_item=null;
                $price_min_volume=null;
                $price_min_tonase=null;
                $price_min_item=null;

            }

            if ($request->price_list_id) {
                if ($request->stype_id!=1) {
                    $is_approve=1;
                    if ($request->price_inquery_full!=$request->price_list_price_full) {
                        $is_approve=0;
                    }
                }
            } else {
                $is_approve=0;
            }
            if ($request->stype_id==5) {
                //sewa gudang price
                $price_volume=$request->price_inquery_volume;
                $price_tonase=$request->price_inquery_tonase;
                $price_volume_default=$request->price_list_price_volume;
                $price_tonase_default=$request->price_list_price_tonase;
            } else {
                $service = Service::find($request->service_id);
                if($service->is_handling == 1 || $service->is_stuffing == 1 ) {
                    $price_volume=$request->price_inquery_handling_volume;
                    $price_tonase=$request->price_inquery_handling_volume;
                    $price_item=$request->price_inquery_item;
                }
            }
            if ($service->service_type_id == 5 || $service->service_type_id == 15) {
                $request->validate([
                  'over_storage_price' => 'required|numeric|min:1'
                ],[
                  'over_storage_price.required' => 'Tarif over storage tidak boleh kosong'
                ]);
                $freeStorageDays=$request->free_storage_day ?? 0;
                $overStoragePrice=$request->over_storage_price;
                $request->price_inquery_full = $request->over_storage_price;
            } else {
                $freeStorageDays=0;
                $overStoragePrice=0;
            }

            if($request->handling_type == 2) {
                $price_volume = 0;
                $price_tonase = 0;
                $price_item = 0;
            }
            $rt=Trayek::find($request->route_id);
            $piece=Piece::find($request->piece_id);
            $slug=str_random(6);
            if (isset($iddetail)) {
                QuotationDetail::find($iddetail)->update([
                    'route_id' => $request->route_id,
                    'service_id' => $request->service_id,
                    'combined_price_id' => $request->combined_price_id,
                    'piece_id' => $request->piece_id,
                    'commodity_id' => ($request->commodity_id?:1),
                    'moda_id' => $request->moda_id,
                    'vehicle_type_id' => $request->vehicle_type_id,
                    'piece_name' => ($piece?$piece->name:null),
                    'description' => $request->description,
                    'imposition' => $request->imposition,
                    // 'cost' => 0,
                    'pallet_price' => $request->pallet_price ?? 0,
                    'price_inquery_tonase' => $price_tonase,
                    'price_inquery_min_tonase' => $price_min_tonase,
                    'price_contract_tonase' => $price_tonase,
                    'price_contract_min_tonase' => $price_min_tonase,
                    'price_inquery_volume' => $price_volume,
                    'price_inquery_min_volume' => $price_min_volume,
                    'price_contract_volume' => $price_volume,
                    'price_contract_min_volume' => $price_min_volume,
                    'price_inquery_item' => $price_item,
                    'price_inquery_min_item' => $price_min_item,
                    'price_contract_item' => $price_item,
                    'price_contract_min_item' => $price_min_item,
                    'price_inquery_full' => $request->price_inquery_full,
                    'price_contract_full' => $request->price_inquery_full,
                    'price_inquery_handling_tonase' => $request->price_inquery_handling_tonase,
                    'price_contract_handling_tonase' => $request->price_inquery_handling_tonase,
                    'price_inquery_handling_volume' => $request->price_inquery_handling_volume,
                    'price_contract_handling_volume' => $request->price_inquery_handling_volume,
                    'container_type_id' => $request->container_type_id,
                    'rack_id' => $request->rack_id,
                    'service_type_id' => $request->stype_id,
                    'is_generate' => $request->is_generate,
                    'route_cost_id' => $request->cost_template,
                    'price_list_id' => $request->price_list_id,
                    'warehouse_id' => $request->warehouse_id,
                    'is_approve' => $is_approve??0,
                    'price_list_price_full' => $request->price_list_price_full??0,
                    'price_list_price_volume' => $price_volume_default??0,
                    'price_list_price_tonase' => $price_tonase_default??0,
                    'price_list_price_item' => $price_item_default??0,
                    'free_storage_day' => $freeStorageDays ?? 0,
                    'over_storage_price' => $overStoragePrice,
                    'handling_type' => $request->handling_type ?? 1,
                    'min_type' => $request->min_type
                ]);
                $qt=QuotationDetail::find($iddetail);
                if(isset($request->detail)) {
                    DB::table('quotation_price_details')->whereHeaderId($qt->id)->delete();
                    $detail = collect($request->detail)->map(function($value) use($qt) {
                    $value['header_id'] = $qt->id;
                    $value['price'] = !isset($value['price']) ? 0 : $value['price'];
                        return $value;
                    });
                    $price_full = $detail->reduce(function($x, $y){
                    return $x + $y['price'];
                    });
                    $qt->update(['price_inquery_full' => $price_full, 'price_contract_full' => $price_full]);
                    DB::table('quotation_price_details')->insert($detail->toArray());
                }

            if($request->stype_id == 1) 
            {
                if($request->min_type == 1) 
                {
                    DB::table('price_list_minimum_details')->where('quotation_detail_id', $iddetail)->delete();
                }
            }
            } else {
            $qt=QuotationDetail::create([
              'header_id' => $id,
              'price_type' => $request->price_type,
              'route_id' => $request->route_id,
              'service_id' => $request->service_id,
              'combined_price_id' => $request->combined_price_id,
              'piece_id' => $request->piece_id,
              'commodity_id' => ($request->commodity_id?:1),
              'moda_id' => $request->moda_id,
              'vehicle_type_id' => $request->vehicle_type_id,
              'piece_name' => ($piece?$piece->name:null),
              'description' => $request->description,
              'imposition' => $request->imposition,
              'cost' => 0,
              'pallet_price' => $request->pallet_price ?? 0,
              'price_inquery_tonase' => $price_tonase,
              'price_inquery_min_tonase' => $price_min_tonase,
              'price_contract_tonase' => $price_tonase,
              'price_contract_min_tonase' => $price_min_tonase,
              'price_inquery_volume' => $price_volume,
              'price_inquery_min_volume' => $price_min_volume,
              'price_contract_volume' => $price_volume,
              'price_contract_min_volume' => $price_min_volume,
              'price_inquery_item' => $price_item,
              'price_inquery_min_item' => $price_min_item,
              'price_contract_item' => $price_item,
              'price_contract_min_item' => $price_min_item,
              'price_inquery_full' => $request->price_inquery_full,
              'price_contract_full' => $request->price_inquery_full,
              'price_inquery_handling_tonase' => $request->price_inquery_handling_tonase,
              'price_contract_handling_tonase' => $request->price_inquery_handling_tonase,
              'price_inquery_handling_volume' => $request->price_inquery_handling_volume,
              'price_contract_handling_volume' => $request->price_inquery_handling_volume,
              'container_type_id' => $request->container_type_id,
              'warehouse_id' => $request->warehouse_id,
              'service_type_id' => $request->stype_id,
              'is_generate' => 1,
              'route_cost_id' => $request->cost_template,
              'price_list_id' => $request->price_list_id,
              'is_approve' => $is_approve??0,
              'price_list_price_full' => $request->price_list_price_full??0,
              'price_list_price_volume' => $price_volume_default??0,
              'price_list_price_tonase' => $price_tonase_default??0,
              'price_list_price_item' => $price_item_default??0,
              'slug' => $slug,
              'free_storage_day' => $freeStorageDays,
              'over_storage_price' => $overStoragePrice,
              'handling_type' => $request->handling_type ?? 1,
              'min_type' => $request->min_type
            ]);
            $iddetail = $qt->id;

            if(isset($request->detail)) {
              $detail = collect($request->detail)->map(function($value) use($qt) {
                  $value['header_id'] = $qt->id;
                  $value['price'] = !isset($value['price']) ? 0 : $value['price'];
                  return $value;
              });
              $price_full = $detail->reduce(function($x, $y){
                  return $x + $y['price'];
              });
              $qt->update(['price_inquery_full' => $price_full, 'price_contract_full' => $price_full]);
              DB::table('quotation_price_details')->insert($detail->toArray());
            }

            if (isset($is_approve) && $is_approve==0) {
              $userList=DB::table('notification_type_users')
              ->leftJoin('users','users.id','=','notification_type_users.user_id')
              ->whereRaw("notification_type_users.notification_type_id = 12")
              ->select('users.id','users.is_admin','users.company_id')->get();
              // dd($userList);
              $q=Quotation::find($id);
              $n=Notification::create([
                'notification_type_id' => 12,
                'name' => 'Ada Item Quotation yang memerlukan persetujuan!',
                'description' => 'No. Quotation '.$q->code,
                'slug' => $slug,
                'route' => 'marketing.inquery.show.detail',
                'parameter' => json_encode(['id' => $q->id])
              ]);
              // dd($n);
              foreach ($userList as $key => $value) {
                if ($value->is_admin) {
                  NotificationUser::create([
                    'notification_id' => $n->id,
                    'user_id' => $value->id
                  ]);
                } else {
                  if ($value->company_id==auth()->user()->company_id) {
                    NotificationUser::create([
                      'notification_id' => $n->id,
                      'user_id' => $value->id
                    ]);
                  }
                }
              }
            }

            if($request->stype_id == 1)
            {
              if($request->min_type == 2) 
              {
                    foreach($request->minimal_detail as $item) {
                      DB::table('price_list_minimum_details')->insert([
                        'quotation_detail_id' => $qt->id,
                        'price_per_kg' => $item['price_per_kg'] ?? 0,
                        'min_kg' => $item['min_kg'] ?? 0,
                        'price_per_m3' => $item['price_per_m3'] ?? 0,
                        'min_m3' => $item['min_m3'] ?? 0,
                        'price_per_item' => $item['price_per_item'] ?? 0,
                        'min_item' => $item['min_item'] ?? 0,
                        'created_at' => \Carbon\Carbon::now(),
                        'updated_at' => \Carbon\Carbon::now()
                      ]);
                    }
                    $quotDetail = QuotationDetail::find($qt->id);
                    $quotDetail->price_inquery_tonase = $request->price_tonase ?? 0;
                    $quotDetail->price_inquery_min_tonase = $request->min_tonase ?? 0;
                    $quotDetail->price_inquery_volume = $request->price_volume ?? 0;
                    $quotDetail->price_inquery_min_volume = $request->min_volume ?? 0;
                    $quotDetail->price_inquery_item = $request->price_item ?? 0;
                    $quotDetail->price_inquery_min_item = $request->min_item ?? 0;
                    $quotDetail->price_contract_tonase = $request->price_tonase ?? 0;
                    $quotDetail->price_contract_min_tonase = $request->min_tonase ?? 0;
                    $quotDetail->price_contract_volume = $request->price_volume ?? 0;
                    $quotDetail->price_contract_min_volume = $request->min_volume ?? 0;
                    $quotDetail->price_contract_item = $request->price_item ?? 0;
                    $quotDetail->price_contract_min_item = $request->min_item ?? 0;
                    $quotDetail->save();
              }
            }
            }

            if (isset($request->cost_template)) {
            if (!isset($iddetail)) {
              $cost=0;
              $rc=RouteCost::find($request->cost_template);
              foreach ($rc->details as $val) {
                QuotationCost::create([
                  'header_id' => $id,
                  'cost_type_id' => $val->cost_type_id,
                  'vendor_id' => $val->cost_type->vendor_id,
                  'created_by' => auth()->id(),
                  'total' => $val->total_liter,
                  'is_internal' => $val->is_internal,
                  'description' => $val->description,
                  'cost' => $val->cost,
                  'total_cost' => $val->cost,
                  'quotation_detail_id' => $qt->id,
                ]);
                $cost+=$val->cost;
              }
            }
            }
            $this->setPriceByMinimum($iddetail);
            QD::setMainPriceForMultipleMinimum($iddetail);
            DB::commit();
        } catch (Exception $e) {
            $status_code = 421;
            $msg = $e->getMessage();
        }
      
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      $qh=QuotationDetail::find($id);
      Notification::where('slug', $qh->slug)->delete();
      $qh->delete();
      DB::commit();
      return Response::json(null);
    }

    public function reject($id)
    {
      DB::beginTransaction();
      $q=Quotation::find($id);
      Notification::where('slug', $q->slug)->delete();
      $q->update([
        'status_approve' => DB::raw("status_approve-1")
      ]);

      DB::commit();
      return Response::json(null);
    }

    public function ajukan($id)
    {
      DB::beginTransaction();
      $slug=str_random(6);
      Quotation::find($id)->update([
        'submit_by' => auth()->id(),
        'status_approve' => 2,
        'slug' => $slug
      ]);

      $userList=DB::table('notification_type_users')
      ->leftJoin('users','users.id','=','notification_type_users.user_id')
      ->whereRaw("notification_type_users.notification_type_id = 5")
      ->select('users.id','users.is_admin','users.company_id')->get();
      $q=Quotation::find($id);
      $n=Notification::create([
        'notification_type_id' => 5,
        'name' => 'Ada Quotation yang memerlukan persetujuan!',
        'description' => 'No. Quotation '.$q->code,
        'slug' => $slug,
        'route' => 'marketing.inquery.show',
        'parameter' => json_encode(['id' => $q->id])
      ]);
      // dd($n);
      foreach ($userList as $key => $value) {
        if ($value->is_admin) {
          NotificationUser::create([
            'notification_id' => $n->id,
            'user_id' => $value->id
          ]);
        } else {
          if ($value->company_id==auth()->user()->company_id) {
            NotificationUser::create([
              'notification_id' => $n->id,
              'user_id' => $value->id
            ]);
          }
          //abaikan
        }
      }


      DB::commit();
      return Response::json(null);
    }
    public function approve($id)
    {
      DB::beginTransaction();
      $cs=CustomerStage::where('is_close_deal', 1)->first();
      Quotation::find($id)->update([
        'approve_by' => auth()->id(),
        'date_approve' => date('Y-m-d'),
        'customer_stage_id' => $cs->id??DB::raw('customer_stage_id'),
        'status_approve' => 3
      ]);

      DB::commit();
      return Response::json(null);
    }
    public function approve_manager($id)
    {
      DB::beginTransaction();
      $cs=CustomerStage::where('is_close_deal', 1)->first();
      Quotation::find($id)->update([
        'approve_manager_by' => auth()->id(),
        'date_approve' => date('Y-m-d'),
        'customer_stage_id' => $cs->id??DB::raw('customer_stage_id'),
        'status_approve' => 3
      ]);

      DB::commit();
      return Response::json(null);
    }
    public function approve_direction($id)
    {
      DB::beginTransaction();
      $cs=CustomerStage::where('is_close_deal', 1)->first();
      Quotation::find($id)->update([
        'approve_direction_by' => auth()->id(),
        'date_approve' => date('Y-m-d'),
        'customer_stage_id' => $cs->id??DB::raw('customer_stage_id'),
        'status_approve' => 3
      ]);

      DB::commit();
      return Response::json(null);
    }

    public function add_contract($id)
    {
      $data=Quotation::with('company','customer')->where('id', $id)->first();
      if ($data->status_approve!=3) {
        return Response::json(['message' => 'Maaf Anda tidak memiliki izin untuk mengakses halaman ini'],500);
      }
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_contract(Request $request, $id)
    {
      $request->validate([
        'date_start_contract' => 'required',
        'date_end_contract' => 'required',
        'sales_commision' => 'required|integer',
      ]);

      DB::beginTransaction();
      $q=Quotation::find($id);
      $code = new TransactionCode($q->company_id, "contract");
      $code->setCode();
      $trx_code = $code->getCode();

      Quotation::find($id)->update([
        'date_contract' => dateNowDB(),
        'date_start_contract' => dateDB($request->date_start_contract),
        'date_end_contract' => dateDB($request->date_end_contract),
        'description_contract' => $request->description_contract,
        'is_contract' => 1,
        'sales_commision' => $request->sales_commision,
        'price_full_contract' => DB::raw('price_full_inquery'),
        'contract_by' => auth()->id(),
        'no_contract' => $trx_code,
        'status_approve' => 4
      ]);
      $qt=Quotation::find($id);

      if(isset($qt->lead_id)) {
          Lead::find($qt->lead_id)->update([
            'step' => 5
          ]);
        }
      if (isset($qt->inquery)) {
        $i = Inquery::find($qt->inquery->id);

        $i->update([
          'status' => 4
        ]);
        if(isset($i->lead_id)) {
          Lead::find($i->lead_id)->update([
            'step' => 5
          ]);
        }
      }
      if (isset($qt->lead)) {
        Lead::find($qt->lead->id)->update([
          'step' => 5
        ]);
      }


      DB::commit();

      return Response::json(null);
    }

    /*
      Date : 28-05-2021
      Description : Index data quotation file
      Developer : Didin
      Status : Edit
    */
    public function document($id)
    {
        $data['item'] = QF::index($id);
        return Response::json($data, 200);
    }

    public function upload_file(Request $request, $id)
    {
      $request->validate([
        'file' => 'required',
        'name' => 'required'
      ]);

      try {
        $file=$request->file('file');
        $filename="INQUERY_".$id."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();
        $file->move(public_path('files'), $filename);

        DB::beginTransaction();
        QuotationFile::create([
            'header_id' => $id,
            'name' => $request->name,
            'file_name' => 'files/'.$filename,
            'date_upload' => date('Y-m-d'),
            'extension' => $file->getClientOriginalExtension()
        ]);
        DB::commit();

        return Response::json(["message" => "OK"]);
      } catch(Exception $e) {
        return Response::json(["message" => $e->getMessage()]);
      }
    }

    public function cari_route_cost(Request $request)
    {
      $tr=Service::find($request->service_id);
      if ($tr->service_type_id==3) {
        $t=RouteCost::with('trayek')->whereRaw("route_id = $request->route_id AND vehicle_type_id = $request->vehicle_type_id")->get();
      } else {
        $t=RouteCost::with('trayek')->whereRaw("route_id = $request->route_id AND container_type_id = $request->container_type_id")->get();
      }
      return Response::json($t, 200, [], JSON_NUMERIC_CHECK);
    }

    public function detail_cost($id)
    {
      $i=QuotationDetail::with('service','route','moda','container_type','rack','commodity','vehicle_type','header','header.company','header.customer')->where('id', $id)->first();
      return Response::json($i, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_detail_cost(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'cost_type_id' => 'required',
        'vendor_id' => 'required',
        'is_internal' => 'required',
        'total_cost' => 'required',
        'quotation_detail_id' => 'required',
      ]);
      DB::beginTransaction();
      $cp=CostType::find($request->cost_type_id);
      QuotationCost::create([
        'header_id' => $id,
        'cost_type_id' => $cp->id,
        'vendor_id' => $request->vendor_id,
        'created_by' => auth()->id(),
        'total' => ($cp->is_bbm==1?$request->total:1),
        'is_internal' => $request->is_internal,
        'cost' => ($cp->is_bbm==1?$request->cost:$request->total_cost),
        'total_cost' => $request->total_cost,
        'quotation_detail_id' => $request->quotation_detail_id,
      ]);

      $quotation = Quotation::find($id);
      $quotation_detail = QuotationDetail::find($request->quotation_detail_id);

      /* disable store vendor price
      
      VendorPrice::create([
        "cost_type_id" => $request->cost_type_id,
        "date" => Carbon::now(),
        "cost_category" => 1,
        "vendor_id" => $request->vendor_id,
        "company_id" => $quotation->company_id,
        "route_id" => $quotation_detail->route_id,
        'commodity_id' => $quotation_detail->commodity_id,
        "name" => $cp->name,
        "piece_id" => $quotation_detail->piece_id,
        "service_id" => $quotation_detail->service_id,
        "moda_id" => $quotation_detail->moda_id,
        "vehicle_type_id" => $quotation_detail->vehicle_type_id,
        "description" => $request->description,
        "min_tonase" => $quotation_detail->price_inquery_min_tonase,
        "price_tonase" => $request->price_inquery_min_tonase,
        "min_volume" => $request->price_inquery_min_volume,
        "price_volume" => $quotation_detail->price_inquery_min_volume,
        "min_item" => $request->min_item,
        "price_item" => $quotation_detail->price_inquery_item,
        "price_full" => $quotation_detail->price_inquery_full,
        "piece_name" => $quotation_detail->piece_name,
        "created_by" => auth()->id(),
        'price_handling_tonase' => $quotation_detail->price_inquery_handling_tonase,
        'price_handling_volume' => $quotation_detail->price_inquery_handling_volume,
        'rack_id' => $quotation_detail->rack_id,
        'container_type_id' => $quotation_detail->container_type_id,
        'service_type_id' => @$quotation_detail->service->service_type->id,
      ]);
      */
      DB::commit();
      // QuotationDetail::find($request->quotation_detail_id)->update(['cost' => $request->cost]);
      $data=DB::table('quotation_costs')->where('quotation_detail_id',$request->quotation_detail_id)->sum('total_cost');
      return Response::json(['total_cost' => $data]);
    }

    /**
     * Date : 16-07-2022
     * Description : Merubah query untuk menyesuaikan penghapusan quotation cost
     * Developer : Hendra
     * Status : Edit
     */
    public function delete_detail_cost($id)
    {
      DB::beginTransaction();
      $qc=QuotationCost::find($id);
      QuotationCost::find($id)->delete();
      DB::commit();
      if($qc && $qc->quotation_detail_id){
        $data=DB::table('quotation_costs')->where('quotation_detail_id',$qc->quotation_detail_id)->sum('total_cost');
      } else {
        $data=DB::table('quotation_costs')->where('header_id',$qc->header_id)->sum('total_cost');
      }

      return Response::json(['total_cost' => $data]);
    }

    /**
     * Date : 16-07-2022
     * Description : Menambahkan quotation cost (bukan cost dari service)
     * Developer : Hendra
     * Status : Create
     */
    public function store_cost(Request $request, $id)
    {
      $request->validate([
        'cost_type_id' => 'required',
        'vendor_id' => 'required',
        'is_internal' => 'required',
        'total_cost' => 'required|numeric'
      ]);
      DB::beginTransaction();
      $cp=CostType::find($request->cost_type_id);
      QuotationCost::create([
        'header_id' => $id,
        'cost_type_id' => $cp->id,
        'vendor_id' => $request->vendor_id,
        'created_by' => auth()->id(),
        'total' => ($cp->is_bbm==1?$request->total:1),
        'is_internal' => $request->is_internal,
        'cost' => ($cp->is_bbm==1?$request->cost:$request->total_cost),
        'total_cost' => $request->total_cost,
      ]);

      DB::commit();
      $data=DB::table('quotation_costs')->where('header_id',$id)->sum('total_cost');
      return response()->json(['total_cost' => $data]);
    }

    /**
     * 
     */
    public function store_detail_item(Request $request, $id)
    {
        if($request->quotation_id != $id){
          throw new Exception('Data tidak valid');
        }
        $request->validate([
          'detail_items.*' => 'required',
          'detail_items.*.item_id' => 'required|exists:items,id',
          'detail_items.*.price' => 'required|numeric|min:1',
        ],[
          'detail_items.*.price.min' => 'Harga harus diatas :min'
        ]);

        DB::beginTransaction();
        try {
          $detailItems = collect($request->detail_items);
          $allQi = QuotationItem::where('quotation_id', $id)->get();
          foreach($allQi as $x){
            if($detailItems->isNotEmpty()){
              $oldDi = $allQi->shift();
              $newDI = $detailItems->shift();
  
              $oldDi->item_id = $newDI['item_id'];
              $oldDi->price = $newDI['price'];
              $oldDi->save();
            }
          }
          if($detailItems->isNotEmpty()){
            foreach($detailItems as $item){
              $qi = new QuotationItem();
              $qi->quotation_id = $id;
              $qi->item_id = $item['item_id'];
              $qi->price = $item['price'];
              $qi->save();
            }
          }
  
          if($allQi->isNotEmpty()){
            foreach($allQi as $del){
              $del->delete();
            }
          }

          DB::commit();

          return response()->json(['message'=> 'Detail Item berhasil disimpan'], 200);
        } catch (Exception $e){
          DB::rollBack();

          return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function store_description(Request $request, $id)
    {
      DB::beginTransaction();
      Quotation::find($id)->update([
        'description_inquery' => $request->description_inquery
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function approve_detail($id)
    {
      DB::beginTransaction();
      $qh=QuotationDetail::find($id);
      Notification::where('slug', $qh->slug)->update([
        'is_done' => 1
      ]);
      $qh->update([
        'is_approve' => 1,
        'approve_by' => auth()->id()
      ]);

      DB::commit();

      return Response::json(null);
    }

    public function offering($id)
    {
      $sql="
      SELECT
      	qd.id AS quotation_detail_id,
      	Y.id AS quotation_offer_id,
      	services.name as service_name,
        pieces.name as piece_name,
        services.service_type_id as service_type_id,
        qd.imposition as imposition,
      	routes.name AS route_name,
      	CONCAT( container_types.CODE, ' - ', container_types.NAME ) AS container_name,
      	vehicle_types.NAME AS vehicle_type_name,
      	( CASE qd.service_type_id WHEN 2 THEN 'Kontainer' WHEN 3 THEN 'Unit' WHEN 6 THEN pieces.name ELSE IF(qd.imposition=1,'Kubikasi',IF(qd.imposition=2,'Tonase','Item')) END ) AS imposition_name,
      	(
      	IFNULL( qd.price_inquery_item, 0 ) + IFNULL( qd.price_inquery_volume, 0 ) + IFNULL( qd.price_inquery_tonase, 0 ) + IFNULL( qd.price_inquery_full, 0 )
      	) AS penawaran,
      	IFNULL( S.total_cost, 0 ) AS total_cost,
      	IFNULL( Y.total_offering, 0 ) AS total_offer
      FROM
      	quotation_details AS qd
      	LEFT JOIN ( SELECT total_offering, quotation_detail_id, id FROM quotation_history_offers WHERE status = 1 group by quotation_detail_id ) Y ON Y.quotation_detail_id = qd.id
      	LEFT JOIN ( SELECT sum( total_cost ) AS total_cost, quotation_detail_id FROM quotation_costs GROUP BY quotation_detail_id ) S ON S.quotation_detail_id = qd.id
      	LEFT JOIN routes ON routes.id = qd.route_id
      	LEFT JOIN vehicle_types ON vehicle_types.id = qd.vehicle_type_id
      	LEFT JOIN container_types ON container_types.id = qd.container_type_id
      	LEFT JOIN services ON services.id = qd.service_id
      	LEFT JOIN quotations ON quotations.id = qd.header_id
      	LEFT JOIN pieces ON pieces.id = qd.piece_id
      WHERE quotations.id = $id
      ORDER BY qd.id ASC
      ";
      $data['detail']=DB::select($sql);
      $data['item']=Quotation::find($id);
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_offer(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'id' => 'required',
        'total_offer' => 'required|integer|min:1',
      ]);
      DB::beginTransaction();
      $slug=str_random(6);
      QuotationHistoryOffer::create([
        'quotation_detail_id' => $request->id,
        'price' => $request->penawaran,
        'total_cost' => $request->total_cost,
        'total_offering' => $request->total_offer,
        'create_by' => auth()->id(),
        'slug' => $slug
      ]);

      $sql="
      SELECT
        qd.id AS quotation_detail_id,
        Y.id AS quotation_offer_id,
        quotations.id as quotation_id,
        quotations.code as quotation_code,
        services.name as service_name,
        routes.name AS route_name,
        CONCAT( container_types.CODE, ' - ', container_types.NAME ) AS container_name,
        vehicle_types.NAME AS vehicle_type_name,
        ( CASE qd.service_type_id WHEN 2 THEN 'Kontainer' WHEN 3 THEN 'Unit' WHEN 6 THEN pieces.name ELSE IF(qd.imposition=1,'Kubikasi',IF(qd.imposition,'Tonase','Item')) END ) AS imposition_name,
        (
        IFNULL( qd.price_inquery_item, 0 ) + IFNULL( qd.price_inquery_volume, 0 ) + IFNULL( qd.price_inquery_tonase, 0 ) + IFNULL( qd.price_inquery_full, 0 )
        ) AS penawaran,
        IFNULL( S.total_cost, 0 ) AS total_cost,
        IFNULL( Y.total_offering, 0 ) AS total_offer
      FROM
        quotation_details AS qd
        LEFT JOIN ( SELECT total_offering, quotation_detail_id, id FROM quotation_history_offers WHERE status = 1 group by quotation_detail_id ) Y ON Y.quotation_detail_id = qd.id
        LEFT JOIN ( SELECT sum( total_cost ) AS total_cost, quotation_detail_id FROM quotation_costs GROUP BY quotation_detail_id ) S ON S.quotation_detail_id = qd.id
        LEFT JOIN routes ON routes.id = qd.route_id
        LEFT JOIN vehicle_types ON vehicle_types.id = qd.vehicle_type_id
        LEFT JOIN container_types ON container_types.id = qd.container_type_id
        LEFT JOIN services ON services.id = qd.service_id
        LEFT JOIN quotations ON quotations.id = qd.header_id
        LEFT JOIN pieces ON pieces.id = qd.piece_id
      WHERE qd.id = $request->id
      ORDER BY qd.id ASC
      ";
      $dt=DB::select($sql);
      foreach ($dt as $value) {
        // notif----------------------------------------------
        $percent=($value->total_offer/$value->penawaran)*100;
        if ($percent <= 10 || $value->total_offer <= 50000000) {
          $userList=DB::table('notification_type_users')
          ->leftJoin('users','users.id','=','notification_type_users.user_id')
          ->whereRaw("notification_type_users.notification_type_id = 2")
          ->select('users.id','users.is_admin','users.company_id')->get();
          $n=Notification::create([
            'notification_type_id' => 2,
            'name' => 'Ada Biaya Penawaran yang memerlukan persetujuan Supervisi!',
            'description' => 'No. Quotation '.$value->quotation_code.' pada layanan '.$value->service_name,
            'slug' => $slug,
            'route' => 'marketing.inquery.show.offer',
            'parameter' => json_encode(['id' => $value->quotation_id])
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
        } elseif ($percent <= 15 || $value->total_offer <= 100000000) {
          $userList=DB::table('notification_type_users')
          ->leftJoin('users','users.id','=','notification_type_users.user_id')
          ->whereRaw("notification_type_users.notification_type_id = 3")
          ->select('users.id','users.is_admin','users.company_id')->get();
          $n=Notification::create([
            'notification_type_id' => 3,
            'name' => 'Ada Biaya Penawaran yang memerlukan persetujuan Manajer!',
            'description' => 'No. Quotation '.$value->quotation_code.' pada layanan '.$value->service_name,
            'slug' => $slug,
            'route' => 'marketing.inquery.show.offer',
            'parameter' => json_encode(['id' => $value->quotation_id])
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
          ->whereRaw("notification_type_users.notification_type_id = 4")
          ->select('users.id','users.is_admin','users.company_id')->get();
          $n=Notification::create([
            'notification_type_id' => 4,
            'name' => 'Ada Biaya Penawaran yang memerlukan persetujuan Direksi!',
            'description' => 'No. Quotation '.$value->quotation_code.' pada layanan '.$value->service_name,
            'slug' => $slug,
            'route' => 'marketing.inquery.show.offer',
            'parameter' => json_encode(['id' => $value->quotation_id])
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

        // end notif---------------------------------------------

      }
      DB::commit();

      return Response::json(null);
    }

    public function reject_offer($id)
    {
      DB::beginTransaction();
      $qh=QuotationHistoryOffer::find($id);
      // dd($qh);
      Notification::where('slug', $qh->slug)->update([
        'is_done' => 1
      ]);
      $qh->update([
        'status' => 3,
        'reject_by' => auth()->id(),
        'date_reject' => Carbon::now()
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function approve_offer($id)
    {
      DB::beginTransaction();
      $qh=QuotationHistoryOffer::find($id);
      Notification::where('slug', $qh->slug)->update([
        'is_done' => 1
      ]);
      $qd=QuotationDetail::find($qh->quotation_detail_id);
      if ($qd->service_type_id!=1) {
        $qd->update([
          'price_inquery_full' => ($qh->price-$qh->total_offering),
        ]);
      } else {
        if ($qd->imposition==1) {
          $qd->update([
            'price_inquery_volume' => ($qh->price-$qh->total_offering),
          ]);
        } elseif ($qd->imposition==2) {
          $qd->update([
            'price_inquery_tonase' => ($qh->price-$qh->total_offering),
          ]);
        } else {
          $qd->update([
            'price_inquery_item' => ($qh->price-$qh->total_offering),
          ]);
        }
      }
      $qh->update([
        'approve_by' => auth()->id(),
        'date_approve' => Carbon::now(),
        'status' => 2
      ]);
      DB::commit();

      return Response::json(null);
    }
    public function cancel_quotation($id)
    {
      DB::beginTransaction();
      Quotation::find($id)->update([
        'status_approve' => 6,
        'cancel_quotation_by' => auth()->id(),
        'cancel_quotation_date' => Carbon::now()
      ]);
      Inquery::where('quotation_id', $id)->update([
        'status' => 7
      ]);
      Lead::where('quotation_id', $id)->update([
        'step' => 9
      ]);
      DB::commit();
    }

    public function cancel_cancel_quotation($id)
    {
      DB::beginTransaction();
      Quotation::find($id)->update([
        'status_approve' => 1,
      ]);
      Inquery::where('quotation_id', $id)->update([
        'status' => 3
      ]);
      Lead::where('quotation_id', $id)->update([
        'step' => 4
      ]);
      DB::commit();
    }

    /*
      Date : 02-12-2020
      Description : Update harga jika layanan LCL dan minimum adalah multiple
      Developer : Didin
      Status : Create
    */
    public function setPriceByMinimum($quotation_detail_id) {
        $quotation_detail = DB::table('quotation_details')
        ->join('services', 'services.id', 'quotation_details.service_id')
        ->where('quotation_details.id', $quotation_detail_id)
        ->select('services.service_type_id', 'quotation_details.min_type', 'quotation_details.imposition')
        ->first();

        if($quotation_detail) {
            if($quotation_detail->service_type_id == 1 && $quotation_detail->min_type == 2) {
                $column = null;
                switch($quotation_detail->imposition) {
                    case 1: 
                        $column = 'price_per_m3';
                        $imposition_name = 'volume';
                        break;
                    case 2:
                        $column = 'price_per_kg';
                        $imposition_name = 'tonase';
                        break;
                    case 3:
                        $column = 'price_per_item';
                        $imposition_name = 'item';
                        break;
                }
                if($column) {
                    $minimums = (array) DB::table('price_list_minimum_details')
                    ->whereQuotationDetailId($quotation_detail_id)
                    ->orderBy($column, 'asc')
                    ->first();
                    DB::table('quotation_details')
                    ->whereId($quotation_detail_id)
                    ->update([
                        'price_inquery_' . $imposition_name => $minimums[$column],
                        'price_contract_' . $imposition_name => $minimums[$column],
                    ]);
                }
            }
        }

    }

    public function store_minimal_detail(Request $request)
    {
        DB::beginTransaction();

        try {
            $p = QuotationDetail::find($request->quotation_detail_id);
            $p->min_type = 2;
            $p->price_inquery_tonase = 0;
            $p->price_inquery_min_tonase = 0;
            $p->price_inquery_volume = 0;
            $p->price_inquery_min_volume = 0;
            $p->price_inquery_item = 0;
            $p->price_inquery_min_item = 0;
            $p->price_contract_tonase = 0;
            $p->price_contract_min_tonase = 0;
            $p->price_contract_volume = 0;
            $p->price_contract_min_volume = 0;
            $p->price_contract_item = 0;
            $p->price_contract_min_item = 0;
            $p->save();

            DB::table('price_list_minimum_details')->insert([
                'quotation_detail_id' => $request->quotation_detail_id,
                'price_per_kg' => $request->price_per_kg ?? 0,
                'min_kg' => $request->min_kg ?? 0,
                'price_per_m3' => $request->price_per_m3 ?? 0,
                'min_m3' => $request->min_m3 ?? 0,
                'price_per_item' => $request->price_per_item ?? 0,
                'min_item' => $request->min_item ?? 0,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now()
            ]);
            $quotation_detail_id = $request->quotation_detail_id;
            QD::setMainPriceForMultipleMinimum($quotation_detail_id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }

        return Response::json(null);
    }

    public function update_minimal_detail($id, Request $request)
    {
        $request->validate([
          'price_per_kg' => 'required',
          'min_kg' => 'required',
          'price_per_m3' => 'required',
          'min_m3' => 'required',
          'price_per_item' => 'required',
          'min_item' => 'required'
        ]);
        DB::beginTransaction();
        $status_code = 200;
        $msg = 'Data successfully saved';
        try {
            $dt = PriceListMinimumDetail::show($id);
            $quotation_detail_id = $dt->quotation_detail_id;
            DB::table('price_list_minimum_details')->where('id', $id)->update([
                'price_per_kg' => $request->price_per_kg,
                'min_kg' => $request->min_kg,
                'price_per_m3' => $request->price_per_m3,
                'min_m3' => $request->min_m3,
                'price_per_item' => $request->price_per_item,
                'min_item' => $request->min_item,
                'updated_at' => \Carbon\Carbon::now()
            ]);
            QD::setMainPriceForMultipleMinimum($quotation_detail_id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;
        return Response::json($data, $status_code);
    }

    public function destroy_minimal_detail($id)
    {
        DB::beginTransaction();
        $msg = 'Data successfully saved';
        $status_code = 200;
        try {
            $dt = PriceListMinimumDetail::show($id);
            $quotation_detail_id = $dt->quotation_detail_id;
            DB::table('price_list_minimum_details')->where('id', $id)->delete();
            QD::setMainPriceForMultipleMinimum($quotation_detail_id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $msg = $e->getMessage();
            $status_code = 421;
        }

        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }

    public function storeQuotationItem(Request $request, $quotation_id, $item_id) {
        $data['message'] = 'OK';
        $status_code = 200;
        try {
            Q::storeQuotationItem($quotation_id, $item_id, $request->price);
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return Response::json($data, $status_code);
    }
}
