<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPriceRequest;
use App\Model\Contact;
use App\Model\Bank;
use App\Model\Account;
use App\Model\Company;
use App\Model\City;
use App\Model\AddressType;
use App\Model\ContactAddress;
use App\Model\ContactFile;
use App\Model\CustomerPrice;
use App\Model\Route as Trayek;
use App\Model\VehicleType;
use App\Model\Moda;
use App\Model\Commodity;
use App\Model\Piece;
use App\Model\Service;
use App\Model\PriceList;
use App\Model\ServiceType;
use App\Model\ContainerType;
use App\Model\Rack;
use App\Model\CombinedPrice;
use Response;
use DB;

class CustomerPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['company']=companyAdmin(auth()->id());
        $data['customer']=Contact::whereRaw("is_pelanggan = 1")->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['company']=companyAdmin(auth()->id());
      $data['commodity']=Commodity::all();
      $data['service']=Service::with('service_type')->where('is_warehouse', 0)->get();
      $data['service_warehouse']= Service::where('is_warehouse', 1)->get();
      $data['piece']=Piece::all();
      $data['combined_price']=CombinedPrice::select('id')->get();
      $data['moda']=Moda::all();
      $data['commodity']=Commodity::all();
      $data['vehicle_type']=VehicleType::all();
      $data['container_type']=ContainerType::all();
      $data['route']=Trayek::all();
      $data['rack']=Rack::all();
      $data['customer']=Contact::whereRaw("is_pelanggan = 1")->get();
      $data['item'] = ['company_id' => auth()->user()->company_id];
      // $data['service_type']=ServiceType::orderBy('id')->get();
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CustomerPriceRequest $request)
    {
      $request->validated();

      DB::beginTransaction();
      $service=DB::table('services')->where('id', $request->service_id)->first();
      if ($service && $service->is_wh_rent==1) {
        $request->validate([
          'free_storage_day' => 'required|numeric|min:1',
          'over_storage_price' => 'required|numeric|min:1'
        ],[
          'free_storage_day.required' => 'Jumlah hari free storage harus ditentukan'
        ]);
        $freeStorage=$request->free_storage_day;
        $overStoragePrice=$request->over_storage_price;
      } else {
        $freeStorage=0;
        $overStoragePrice=0;
      }

      $piece=Piece::find($request->piece_id);
      $c = CustomerPrice::create([
        "is_warehouse" => $request->is_warehouse,
        "customer_id" => $request->customer_id,
        "company_id" => $request->company_id,
        "route_id" => $request->route_id,
        'commodity_id' => ($request->commodity_id?:1),
        "name" => $request->name,
        "piece_id" => $request->piece_id,
        "service_id" => $request->service_id,
        "combined_price_id" => $request->combined_price_id,
        "moda_id" => $request->moda_id,
        "vehicle_type_id" => $request->vehicle_type_id,
        "description" => $request->description,
        "min_tonase" => $request->min_tonase,
        "price_tonase" => $request->price_tonase,
        "min_volume" => $request->min_volume,
        "price_volume" => $request->price_volume,
        "min_item" => $request->min_item,
        "price_item" => $request->price_item,
        "price_full" => $request->price_full,
        "piece_name" => ($piece?$piece->name:null),
        "created_by" => auth()->id(),
        'price_handling_tonase' => $request->price_handling_tonase,
        'price_handling_volume' => $request->price_handling_volume,
        'rack_id' => $request->rack_id,
        'container_type_id' => $request->container_type_id,
        'free_storage_day' => $freeStorage,
        'over_storage_price' => $overStoragePrice,
      ]);


      if(isset($request->detail)) {
        $detail = collect($request->detail)->map(function($value) use($c) {
            $value['header_id'] = $c->id;
            $value['price'] = !isset($value['price']) ? 0 : $value['price'];
            return $value;
        });
        $price_full = $detail->reduce(function($x, $y){
            return $x + $y['price'];
        });
        $c->update(['price_full' => $price_full]);
        DB::table('customer_price_details')->insert($detail->toArray());
      }
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
        $c=CustomerPrice::with('company', 'customer', 'commodity','service','moda','vehicle_type','route','rack','container_type','service_type', 'detail.service.service_type')->where('id', $id)->first();
      return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
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
      $data['commodity']=Commodity::all();
      $data['combined_price']=CombinedPrice::select('id')->get();
      $data['service']=Service::with('service_type')->where('is_warehouse', 0)->get();
      $data['service_warehouse']= Service::where('is_warehouse', 1)->get();
      $data['piece']=Piece::all();
      $data['moda']=Moda::all();
      $data['commodity']=Commodity::all();
      $data['vehicle_type']=VehicleType::all();
      $data['container_type']=ContainerType::all();
      $data['route']=Trayek::all();
      $data['rack']=Rack::all();
      $data['item']=CustomerPrice::with('service', 'detail.service.service_type')->where('customer_prices.id', $id)->selectRaw('customer_prices.*')->first();
      $data['customer']=Contact::whereRaw("is_pelanggan = 1")->get();
      // $data['service_type']=ServiceType::orderBy('id')->get();
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
        'combined_price_id' => 'required_if:is_warehouse,2',
        'company_id' => 'required',
        'route_id' => 'required_if:stype_id,1|required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4',
        'commodity_id' => 'required_if:stype_id,5',
        'name' => 'required',
        'piece_id' => 'required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7',
        'moda_id' => 'required_if:stype_id,1',
        'vehicle_type_id' => 'required_if:stype_id,1|required_if:stype_id,3|required_if:stype_id,4',
        "min_tonase" => 'required_if:stype_id,1',
        "price_tonase" => 'required_if:stype_id,1|required_if:stype_id,5',
        "min_volume" => 'required_if:stype_id,1|required_if:stype_id,5',
        "price_volume" => 'required_if:stype_id,1',
        "min_item" => 'required_if:stype_id,1',
        "price_item" => 'required_if:stype_id,1',
        "price_full" => 'required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7',
        'price_handling_tonase' => 'required_if:stype_id,5',
        'price_handling_volume' => 'required_if:stype_id,5',
        'container_type_id' => 'required_if:stype_id,2',
        'rack_id' => 'required_if:stype_id,5',
      ]);

      DB::beginTransaction();
      $piece=Piece::find($request->piece_id);
      $services = Service::with('service_type')->find($request->service_id);
      $c = CustomerPrice::find($id);
      $c->update([
        "company_id" => $request->company_id,
        "route_id" => $request->route_id,
        'commodity_id' => ($request->commodity_id?:1),
        "name" => $request->name,
        "piece_id" => $request->piece_id,
        "service_id" => $request->service_id,
        "combined_price_id" => $request->combined_price_id,
        "moda_id" => $request->moda_id,
        "vehicle_type_id" => $request->vehicle_type_id,
        "description" => $request->description,
        "min_tonase" => $request->min_tonase,
        "price_tonase" => $request->price_tonase,
        "min_volume" => $request->min_volume,
        "price_volume" => $request->price_volume,
        "min_item" => $request->min_item,
        "price_item" => $request->price_item,
        "price_full" => $request->price_full,
        "piece_name" => ($piece?$piece->name:null),
        "created_by" => auth()->id(),
        'price_handling_tonase' => $request->price_handling_tonase,
        'price_handling_volume' => $request->price_handling_volume,
        'rack_id' => $request->rack_id,
        'container_type_id' => $request->container_type_id,
        'is_warehouse' => $request->is_warehouse
      ]);

      if(isset($request->detail)  && isset($request->combined_price_id)) {
        DB::table('customer_price_details')->whereHeaderId($id)->delete();
        $detail = collect($request->detail)->map(function($value) use($c) {
            $value['header_id'] = $c->id;
            $value['price'] = !isset($value['price']) ? 0 : $value['price'];
            return $value;
        });
        $price_full = $detail->reduce(function($x, $y){
            return $x + $y['price'];
        });
        $c->update(['price_full' => $price_full]);
        DB::table('customer_price_details')->insert($detail->toArray());
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
        DB::beginTransaction();
          CustomerPrice::find($id)->delete();
        DB::commit();

        return Response::json(null);
    }
}
