<?php

namespace App\Http\Controllers\Api\v4\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Route as Trayek;
use App\Model\RouteCost;
use App\Model\RouteCostDetail;
use App\Model\Company;
use App\Model\ContainerType;
use App\Model\VehicleType;
use App\Model\City;
use App\Model\Moda;
use App\Model\Commodity;
use App\Model\CostType;
use Response;
use DB;

class RouteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dt = DB::table('routes')
        ->select('id', 'name')
        ->get();
        $data['message'] = 'OK';
        $data['data'] = $dt;

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['city']=City::join('provinces', 'provinces.id', 'cities.province_id')
      ->select('cities.*', 'provinces.name AS province_name', 'provinces.country_id')
      ->get();
      // $data['vehicle_type']=VehicleType::all();

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
        'code' => 'required',
        'name' => 'required',
        'company_id' => 'required',
        'city_from' => 'required',
        'city_to' => 'required',
        'distance' => 'required',
        'duration' => 'required',
        'type_satuan' => 'required'
      ]);
      DB::beginTransaction();
      Trayek::create([
        'code' => $request->code,
        'name' => $request->name,
        'company_id' => $request->company_id,
        'city_from' => $request->city_from,
        'city_to' => $request->city_to,
        'distance' => $request->distance,
        'duration' => $request->duration,
        'type_satuan' => $request->type_satuan,
        'description' => $request->description,
        'created_by' => auth()->id(),
        'is_active' => 1
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
      // $data['trayek']=Trayek::all();
      $data['commodity']=Commodity::all();
      $data['container_type']=ContainerType::all();
      $data['city']=City::all();
      $data['item']=Trayek::with('company:id,name','details','details.commodity','details.container_type','details.vehicle_type')
      ->join('cities AS city_origins', 'city_origins.id', 'routes.city_from')
      ->join('provinces AS province_origins', 'province_origins.id', 'city_origins.province_id')
      ->join('countries AS country_origins', 'country_origins.id', 'province_origins.country_id')
      ->join('cities AS city_destinations', 'city_destinations.id', 'routes.city_to')
      ->join('provinces AS province_destinations', 'province_destinations.id', 'city_destinations.province_id')
      ->join('countries AS country_destinations', 'country_destinations.id', 'province_destinations.country_id')
      ->where('routes.id', $id)
      ->select('routes.*', 'province_origins.country_id AS country_from_id', 'province_destinations.country_id AS country_to_id', 'city_origins.name AS city_origin_name', 'province_origins.name AS province_origin_name', 'country_origins.name AS country_origin_name', 'city_destinations.name AS city_destination_name', 'province_destinations.name AS province_destination_name', 'country_destinations.name AS country_destination_name')
      ->first();
      $time_unit = '';
      if($data['item']->type_satuan == 1) {
        $time_unit = 'Jam';
      } else if($data['item']->type_satuan == 2) {
        $time_unit = 'Hari';
      } else if($data['item']->type_satuan == 3) {
        $time_unit = 'Menit';
      }
      $data['item']->time_unit = $time_unit;

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
      $data['city']=City::all();
      $data['item']=Trayek::find($id);

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
        'code' => 'required',
        'name' => 'required',
        'company_id' => 'required',
        'city_from' => 'required',
        'city_to' => 'required',
      ]);

      DB::beginTransaction();
      Trayek::find($id)->update([
        'code' => $request->code,
        'name' => $request->name,
        'company_id' => $request->company_id,
        'city_from' => $request->city_from,
        'city_to' => $request->city_to,
        'distance' => $request->distance,
        'duration' => $request->duration,
        'type_satuan' => $request->type_satuan,
        'description' => $request->description,
        // 'created_by' => auth()->id(),
        // 'is_active' => 1
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
      DB::beginTransaction();
      Trayek::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function store_cost(Request $request, $id=null)
    {
      // dd($request);
      if (empty($id)) {
        RouteCost::create([
          'route_id' => $request->header_id,
          'commodity_id' => $request->commodity_id,
          'cost' => 0,
          'description' => $request->description,
          'vehicle_type_id' => $request->vehicle_type_id,
          'container_type_id' => $request->container_type_id,
          'is_container' => $request->is_container,
          'created_by' => auth()->id(),
        ]);
      } else {
        RouteCost::find($id)->update([
          // 'route_id' => $request->header_id,
          'commodity_id' => $request->commodity_id,
          // 'cost' => 0,
          'description' => $request->description,
          'vehicle_type_id' => $request->vehicle_type_id,
          'container_type_id' => $request->container_type_id,
          'is_container' => $request->is_container,
        ]);
      }

      return Response::json(null);
    }

    public function delete_cost($id)
    {
      DB::beginTransaction();
      RouteCost::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function cost($id)
    {
      $data['item']=RouteCost::find($id);
      $data['cost_type']=CostType::with('parent')->where('is_invoice', 0)->where('company_id', $data['item']->trayek->company_id)->where('parent_id', '!=', null)->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_detail_cost(Request $request, $id)
    {
      $request->validate([
        'cost_type_id' => 'required',
        'cost' => 'required',
        'is_internal' => 'required',
      ]);
      DB::beginTransaction();
      $ct=CostType::find($request->cost_type_id['id']);
      RouteCostDetail::create([
        'header_id' => $id,
        'cost_type_id' => $request->cost_type_id['id'],
        'created_by' => auth()->id(),
        'cost' => $request->cost,
        'is_bbm' => $ct->is_bbm,
        'is_internal' => $request->is_internal,
        'description' => $request->description,
        'harga_satuan' => ($request->harga_satuan?:0),
        'total_liter' => ($request->total_liter?:1),
      ]);

      // RouteCost::find($id)->update([
      //   'cost' => RouteCost::find($id)->details->sum('cost')
      // ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_detail_cost($id)
    {
      DB::beginTransaction();
      $rtc=RouteCostDetail::find($id);
      RouteCostDetail::find($id)->delete();

      // RouteCost::find($rtc->header_id)->update([
      //   'cost' => RouteCost::find($rtc->header_id)->details->sum('cost')
      // ]);
      DB::commit();

      return Response::json(null);
    }
}
