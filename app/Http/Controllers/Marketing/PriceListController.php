<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Requests\PriceListRequest;
use App\Http\Controllers\Controller;
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
use App\Abstracts\Marketing\PriceList AS PL;
use App\Abstracts\Marketing\PriceListMinimumDetail;
use Response;
use DB;
use Exception;

class PriceListController extends Controller
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
    public function create(CombinedPrice $combinedPrice)
    {
        $data['commodity']=Commodity::select('id', 'name')->get();
        $data['service']=Service::with('service_type')
        ->select('id', 'name', 'service_type_id', 'is_wh_rent')
        ->get();
        $data['moda']=Moda::select('id', 'name')->get();
        $data['commodity']=Commodity::select('id', 'name')->get();
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

            return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
        }
    }

    /**
    * Store a newly created resource in storage.
    *
    * @param  \Illuminate\Http\Request  $request
    * @return \Illuminate\Http\Response
    */
    public function store(PriceListRequest $request)
    {
        $request->validated();

        DB::beginTransaction();
        $msg = 'Data successfully saved';
        $status_code = 200;
        try {
            $service=DB::table('services')->where('id', $request->service_id)->first();
            if ($service->is_wh_rent==1) {
            $request->validate([
                'over_storage_price' => 'required|numeric|min:1'
            ]);
            $freeStorage=$request->free_storage_day ?? 0;
            $overStoragePrice=$request->over_storage_price;
            $request->price_full = $request->over_storage_price;
            } else {
            $freeStorage=0;
            $overStoragePrice=0;
            }
            $piece=Piece::find($request->piece_id);
            /* vehicle type */
            $vehicle_type = $request->vehicle_type_id;
            if(($request->stype_id==1 && $request->ltl_lcl!=1) || !in_array($request->stype_id,[1,2,3,4]) ) $vehicle_type=null;
            /* container type */
            $container_type = $request->container_type_id;
            if(($request->stype_id==1 && $request->ltl_lcl!=2) || !in_array($request->stype_id,[1,2,3,4])) $container_type=null;

            $p = PriceList::create([
                "company_id" => $request->company_id,
                "route_id" => $request->route_id,
                'commodity_id' => ($request->commodity_id?:1),
                "code" => $request->code,
                "name" => $request->name,
                "piece_id" => $request->piece_id,
                "service_id" => $request->service_id,
                "combined_price_id" => $request->combined_price_id,
                "moda_id" => $request->moda_id,
                "vehicle_type_id" => $vehicle_type,
                "description" => $request->description,
                "min_tonase" => $request->min_type == 1 ? $request->min_tonase : 0,
                "price_tonase" => $request->min_type == 1 ? $request->price_tonase : 0,
                "min_volume" => $request->min_type == 1 ? $request->min_volume : 0,
                "price_volume" => $request->min_type == 1 ? $request->price_volume : 0,
                "min_item" => $request->min_type == 1 ? $request->min_item : 0,
                "price_item" => $request->min_type == 1 ? $request->price_item : 0,
                "min_borongan" => isset($request->min_borongan) ? $request->min_borongan : 0,
                "price_borongan" => isset($request->price_borongan) ? $request->price_borongan : 0,
                "price_full" => $request->price_full ?? 0,
                "daily_price" => $request->daily_price ?? 0,
                "pallet_price" => $request->pallet_price ?? 0,
                "piece_name" => ($piece?$piece->name:null),
                "created_by" => auth()->id(),
                'price_handling_tonase' => $request->price_handling_tonase,
                'price_handling_volume' => $request->price_handling_volume,
                'rack_id' => $request->rack_id,
                'container_type_id' => $container_type,
                'warehouse_id' => $request->warehouse_id,
                'is_warehouse' => $request->is_warehouse,
                'free_storage_day' => $freeStorage,
                'over_storage_price' => $overStoragePrice,
                'handling_type' => $request->handling_type ?? 1,
                'min_type' => $request->min_type
            ]);
            $price_list_id = $p->id;

            if($request->stype_id == 1) {
                if($request->min_type == 2) 
                {
                    foreach($request->minimal_detail as $item) {
                        DB::table('price_list_minimum_details')->insert([
                            'price_list_id' => $p->id,
                            'price_per_kg' => $item['price_per_kg'],
                            'min_kg' => $item['min_kg'],
                            'price_per_m3' => $item['price_per_m3'],
                            'min_m3' => $item['min_m3'],
                            'price_per_item' => $item['price_per_item'],
                            'min_item' => $item['min_item'],
                            'created_at' => \Carbon\Carbon::now(),
                            'updated_at' => \Carbon\Carbon::now()
                        ]);
                    }
                    PL::setMainPriceForMultipleMinimum($price_list_id);
                }
            }


            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;
        return Response::json($data, $status_code);
    }

    /*
      Date : 06-03-2020
      Description : Menambah jenis biaya pada tarif umum
      Developer : Didin
      Status : Create
    */
    public function showCost($id) {
        $costs = DB::table('price_list_costs')
        ->join('cost_types', 'cost_types.id', 'price_list_costs.cost_type_id')
        ->join('contacts', 'contacts.id', 'price_list_costs.vendor_id')
        ->whereHeaderId($id)
        ->select('price_list_costs.id', 
        'cost_types.name AS cost_type_name',
        'contacts.name AS vendor_name',
        'price_list_costs.cost_type_id',
        'price_list_costs.vendor_id',
        'price_list_costs.type',
        'price_list_costs.qty',
        'price_list_costs.price',
        'price_list_costs.total_price',
        'price_list_costs.description'
        )
        ->get();
        return Response::json($costs);
    }

    /*
      Date : 06-03-2020
      Description : Menampilkan detail jenis biaya pada tarif umum
      Developer : Didin
      Status : Create
    */

    public function editCost($price_list_cost_id) {
        $costs = DB::table('price_list_costs')
        ->join('cost_types', 'cost_types.id', 'price_list_costs.cost_type_id')
        ->join('contacts', 'contacts.id', 'price_list_costs.vendor_id')
        ->where('price_list_costs.id', $price_list_cost_id)
        ->select('price_list_costs.id', 
        'cost_types.name AS cost_type_name',
        'contacts.name AS vendor_name',
        'price_list_costs.cost_type_id',
        'price_list_costs.vendor_id',
        'price_list_costs.type',
        'price_list_costs.qty',
        'price_list_costs.price',
        'price_list_costs.total_price',
        'price_list_costs.description'
        )
        ->first();
        return Response::json($costs, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
    * Display the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function show($id)
    {
        $c=PriceList::with('company:id,name','warehouse','commodity:id,name','moda:id,name','vehicle_type','route','rack','container_type','service_type', 'service:id,name,is_wh_rent,service_type_id', 'service.combined_price:id,service_id', 'service.combined_price.detail:id,header_id,service_id', 'service.combined_price.detail.service:id,name,service_type_id', 'service.combined_price.detail.service.service_type:id,name')->where('id', $id)->first();
        return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }

    public function showMinimumDetail($id)
    {
        $minDetail = DB::table('price_list_minimum_details')->where('price_list_id', $id)->get();
        return Response::json($minDetail, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 06-03-2020
      Description : Menambah jenis biaya pada tarif umum
      Developer : Didin
      Status : Create
    */
    public function addCost(Request $request, $id)
    {
      $request->validate([
        'cost_type' => 'required',
        'vendor_id' => 'required',
        'qty' => 'required|integer|min:1',
        'total_price' => 'required|integer|min:1',
        'price' => 'required|integer|min:1',
      ]);
          DB::beginTransaction();
          $ctt=DB::table('cost_types')
          ->whereId($request->cost_type)
          ->first();

            $jc=DB::table('price_list_costs')->insert([
              'header_id' => $id,
              'cost_type_id' => $request->cost_type,
              'vendor_id' => $request->vendor_id,
              'qty' => $request->qty,
              'price' => $request->price,
              'total_price' => $request->total_price,
              'description' => $request->description,
              'create_by' => auth()->id(),
              'type' => $request->type,
            ]);
      
        //end notif-------------------------------------
      DB::commit();

      return Response::json(['message' => 'Data berhasil diinput']);
    }

    /*
      Date : 06-03-2020
      Description : Meng-update jenis biaya pada tarif umum
      Developer : Didin
      Status : Create
    */
    public function updateCost(Request $request, $id)
    {
        $request->validate([
            'cost_type' => 'required',
            'vendor_id' => 'required',
            'qty' => 'required|integer|min:1',
            'total_price' => 'required|integer|min:1',
            'price' => 'required|integer|min:1',
        ]);
        DB::beginTransaction();
        DB::table('price_list_costs')->whereId($request->id)->update([
            'cost_type_id' => $request->cost_type,
            'vendor_id' => $request->vendor_id,
            'qty' => $request->qty,
            'price' => $request->price,
            'total_price' => $request->total_price,
            'description' => $request->description,
            'type' => $request->type,
        ]);
        
        DB::commit();

        return Response::json(['message' => 'Data berhasil diinput']);
    }

    /*
      Date : 06-03-2020
      Description : Menghapus jenis biaya pada tarif umum
      Developer : Didin
      Status : Create
    */
    public function deleteCost($id, $price_list_cost_id)
    {
      DB::beginTransaction();
      DB::table('price_list_costs')
      ->whereId($price_list_cost_id)
      ->delete();
      DB::commit();
    }

    /**
    * Show the form for editing the specified resource.
    *
    * @param  int  $id
    * @return \Illuminate\Http\Response
    */
    public function edit($id)
    {
        $data['item']=PriceList::with('detail.service.service_type')->find($id);
        $data['price_list_minimum_detail']=DB::table('price_list_minimum_details')->where('price_list_id', $id)->get();
        $data['commodity']=Commodity::all();
        $data['combined_price']=CombinedPrice::select('id')->get();
        $data['service']=Service::with('service_type')->get();
        $data['piece']=Piece::all();
        $data['moda']=Moda::all();
        $data['commodity']=Commodity::all();
        $data['vehicle_type']=VehicleType::all();
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
            'service_id' => 'required_if:stype_id,0|required_if:stype_id,1',
            'company_id' => 'required',
            'route_id' => 'required_if:stype_id,1|required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4',
            'commodity_id' => 'required_if:stype_id,5',
            'code' => 'required|unique:price_lists,code,'.$id,
            'name' => 'required',
            'piece_id' => 'required_if:stype_id,4|required_if:stype_id,6',
            'moda_id' => 'required_if:stype_id,1',
            'vehicle_type_id' => 'required_if:stype_id,3|required_if:stype_id,4',
            "min_tonase" => 'required_if:stype_id,1',
            "price_tonase" => 'required_if:stype_id,1|required_if:stype_id,5',
            "min_volume" => 'required_if:stype_id,1',
            "price_volume" => 'required_if:stype_id,1|required_if:stype_id,5',
            "min_item" => 'required_if:stype_id,1',
            "price_item" => 'required_if:stype_id,1',
            "price_full" => 'required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7',
            'container_type_id' => 'required_if:stype_id,2',
            'warehouse_id' => 'required_if:stype_id,5',
        ]);

        DB::beginTransaction();
        $msg = 'Data successfully saved';
        $status_code = 200;
        try {
            $piece=Piece::find($request->piece_id);
            $p = PriceList::find($id);
            /* vehicle type */
            $vehicle_type = $request->vehicle_type_id;
            if(($request->stype_id==1 && $request->ltl_lcl!=1) || !in_array($request->stype_id,[1,2,3,4]) ) $vehicle_type=null;
            /* container type */
            $container_type = $request->container_type_id;
            if(($request->stype_id==1 && $request->ltl_lcl!=2) || !in_array($request->stype_id,[1,2,3,4])) $container_type=null;
            
            $p->update([
                "company_id" => $request->company_id,
                "route_id" => $request->route_id,
                'commodity_id' => ($request->commodity_id?:1),
                "combined_price_id" => $request->combined_price_id,
                "code" => $request->code,
                "name" => $request->name,
                "piece_id" => $request->piece_id,
                "service_id" => $request->service_id,
                "combined_price_id" => $request->combined_price_id,
                "moda_id" => $request->moda_id,
                "vehicle_type_id" => $vehicle_type,
                "description" => $request->description,
                "min_tonase" => $request->min_type == 1 ? $request->min_tonase : 0,
                "price_tonase" => $request->min_type == 1 ? $request->price_tonase : 0,
                "min_volume" => $request->min_type == 1 ? $request->min_volume : 0,
                "price_volume" => $request->min_type == 1 ? $request->price_volume : 0,
                'daily_price' => $request->daily_price ?? 0,
                'pallet_price' => $request->pallet_price ?? 0,
                "min_item" => $request->min_type == 1 ? $request->min_item : 0,
                "price_item" => $request->min_type == 1 ? $request->price_item : 0,
                "min_borongan" => isset($request->min_borongan) ? $request->min_borongan : 0,
                "price_borongan" => isset($request->price_borongan) ? $request->price_borongan : 0,
                "price_full" => $request->price_full,
                "piece_name" => ($piece?$piece->name:null),
                "created_by" => auth()->id(),
                'price_handling_tonase' => $request->price_handling_tonase,
                'price_handling_volume' => $request->price_handling_volume,
                'rack_id' => $request->rack_id,
                'container_type_id' => $container_type,
                'service_type_id' => $request->stype_id,
                'warehouse_id' => $request->warehouse_id,
                'is_warehouse' => $request->is_warehouse,
                'over_storage_price' => $request->over_storage_price,
                'free_storage_day' => $request->free_storage_day,
                'handling_type' => $request->handling_type ?? 1,
                'min_type' => $request->min_type
            ]);
            if(isset($request->detail) && isset($request->combined_price_id)) {
                DB::table('price_list_details')->whereHeaderId($id)->delete();
                $detail = collect($request->detail)->map(function($value) use($p) {
                    $value['header_id'] = $p->id;
                    $value['price'] = !isset($value['price']) ? 0 : $value['price'];
                    return $value;
                });
                $price_full = $detail->reduce(function($x, $y){
                    return $x + $y['price'];
                });
                $p->update(['price_full' => $price_full]);
                DB::table('price_list_details')->insert($detail->toArray());
            }

            if($request->stype_id == 1) 
            {
                if($request->min_type == 1) 
                {
                    DB::table('price_list_minimum_details')->where('price_list_id', $p->id)->delete();
                }
            }
            PL::setMainPriceForMultipleMinimum($id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }

        $data['message'] = $msg;

        return Response::json($data, $status_code);
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
        PriceList::find($id)->delete();
        DB::commit();

        return Response::json(null);
    }

    public function cari_tarif(Request $request)
    {
        $wr="service_id = $request->service_id";
        
        if (isset($request->company_id)) {
            $wr.=" AND company_id = ".$request->company_id;
        }

        // if (isset($request->service_id)) {
        //   $wr.=" AND service_id = ".$request->service_id;
        // }

        if ($request->service_type_id==1) {
            $wr.=!$request->route_id ?? " AND route_id = ".$request->route_id;
            $wr.=!$request->moda_id ??" AND moda_id = ".$request->moda_id;
            // $wr.=!$request->vehicle_type_id ?? " AND vehicle_type_id = ".$request->vehicle_type_id;
        } elseif ($request->service_type_id==2) {
            $wr.=" AND route_id = ".$request->route_id;
            $wr.=" AND container_type_id = ".$request->container_type_id;
        } elseif ($request->service_type_id==3) {
            $wr.=" AND route_id = ".$request->route_id;
            $wr.=" AND vehicle_type_id = ".$request->vehicle_type_id;
        } elseif ($request->service_type_id==4) {
            $wr.=" AND route_id = ".$request->route_id;
            $wr.=" AND vehicle_type_id = ".$request->vehicle_type_id;
        } elseif ($request->service_type_id==5) {
            $wr.=" AND commodity_id = ".$request->commodity_id;
            $wr.=" AND warehouse_id = ".$request->warehouse_id;
        }

        $data = PriceList::whereRaw($wr)->first();
        if (isset($data)) {
            if ($request->service_type_id==1) {
                if ($request->imposition==1) {
                    // volume/kubikasi
                    $nom=[
                        'stype' => $request->service_type_id,
                        'tarif' => $data->price_volume,
                        'min' => $data->min_volume,
                        'pl_id' => $data->id,
                    ];
                } elseif ($request->imposition==2) {
                    // tonase
                    $nom=[
                        'stype' => $request->service_type_id,
                        'tarif' => $data->price_tonase,
                        'min' => $data->min_tonase,
                        'pl_id' => $data->id,
                    ];
                } elseif ($request->imposition==3) {
                    // item
                    $nom=[
                        'stype' => $request->service_type_id,
                        'tarif' => $data->price_item,
                        'min' => $data->min_item,
                        'pl_id' => $data->id,
                    ];
                } else {
                    $nom=[
                        'stype' => $request->service_type_id,
                        'tarif' => 0,
                        'min' => 0,
                        'pl_id' => $data->id,
                    ];
                }
            } elseif ($request->service_type_id==5) {
                $nom=[
                    'stype' => $request->service_type_id,
                    'tarif_tonase' => $data->price_tonase,
                    'tarif_volume' => $data->price_volume,
                    'pl_id' => $data->id,
                ];
            } else {
                $nom=[
                    'stype' => $request->service_type_id,
                    'tarif' => $data->price_full,
                    'pl_id' => $data->id,
                ];
            }
        } else {
            $nom=[
                'stype' => $request->service_type_id,
                'tarif' => 0,
                'min' => 0,
                'tarif_tonase' => 0,
                'tarif_handling_tonase' => 0,
                'tarif_volume' => 0,
                'tarif_handling_volume' => 0,
                'pl_id' => null,
            ];
        }

        $nom['wr'] = $wr;

        return Response::json($nom, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_minimal_detail(Request $request)
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
            $p = PriceList::find($request->price_list_id);
            $p->min_type = 2;
            $p->price_tonase = 0;
            $p->min_tonase = 0;
            $p->price_volume = 0;
            $p->min_volume = 0;
            $p->price_item = 0;
            $p->min_item = 0;
            $p->save();

            DB::table('price_list_minimum_details')->insert([
                'price_list_id' => $request->price_list_id,
                'price_per_kg' => $request->price_per_kg,
                'min_kg' => $request->min_kg,
                'price_per_m3' => $request->price_per_m3,
                'min_m3' => $request->min_m3,
                'price_per_item' => $request->price_per_item,
                'min_item' => $request->min_item,
                'created_at' => \Carbon\Carbon::now(),
                'updated_at' => \Carbon\Carbon::now()
            ]);
            $price_list_id = $request->price_list_id;
            PL::setMainPriceForMultipleMinimum($price_list_id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
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
            $price_list_id = $dt->price_list_id;
            DB::table('price_list_minimum_details')->where('id', $id)->update([
                'price_per_kg' => $request->price_per_kg,
                'min_kg' => $request->min_kg,
                'price_per_m3' => $request->price_per_m3,
                'min_m3' => $request->min_m3,
                'price_per_item' => $request->price_per_item,
                'min_item' => $request->min_item,
                'updated_at' => \Carbon\Carbon::now()
            ]);
            PL::setMainPriceForMultipleMinimum($price_list_id);
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
        DB::table('price_list_minimum_details')->where('id', $id)->delete();
        DB::commit();

        return Response::json(null);
    }
}
