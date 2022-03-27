<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\RouteCost;
use App\Model\RouteCostDetail;
use App\Model\Commodity;
use App\Model\VehicleType;
use App\Model\ContainerType;
use App\Model\Route as Trayek;
use Response;
use DB;

class RouteCostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['commodity']=Commodity::all();
      $data['container_type']=ContainerType::all();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
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
    public function store(Request $request)
    {
      $request->validate([
        'route_id' => 'required',
        'commodity_id' => 'required',
        'vehicle_type_id' => 'required_if:is_container,0',
        'container_type_id' => 'required_if:is_container,1',
      ]);
      DB::beginTransaction();
      $r=RouteCost::create([
        'route_id' => $request->route_id,
        'commodity_id' => $request->commodity_id,
        'vehicle_type_id' => $request->vehicle_type_id??null,
        'container_type_id' => $request->container_type_id??null,
        'description' => $request->description,
        'is_container' => $request->is_container??0,
        'created_by' => auth()->id(),
        'cost' => 0,
      ]);
      DB::commit();

      return Response::json(['id' => $r->id,'is_container' => $request->is_container]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=RouteCost::with('vehicle_type','trayek','commodity','container_type', 'trayek.company:id,name')
      ->where('route_costs.id', $id)
      ->first();
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
      $data=RouteCost::find($id);
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
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
        'route_id' => 'required',
        'commodity_id' => 'required',
        'vehicle_type_id' => 'required_if:is_container,0',
        'container_type_id' => 'required_if:is_container,1',
      ]);
      DB::beginTransaction();
      RouteCost::find($id)->update([
        'route_id' => $request->route_id,
        'commodity_id' => $request->commodity_id,
        'container_type_id' => $request->container_type_id,
        'vehicle_type_id' => $request->vehicle_type_id,
        'description' => $request->description,
        // 'created_by' => auth()->id(),
        // 'cost' => 0,
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
      RouteCost::find($id)->delete();
      DB::commit();
      return Response::json(null);
    }

    public function save_as(Request $request)
    {
      // dd($request);
      $request->validate([
        'route_id' => 'required',
        'commodity_id' => 'required',
        'vehicle_type_id' => 'required_if:is_container,0',
        'container_type_id' => 'required_if:is_container,1',
      ]);
      DB::beginTransaction();
      $r=RouteCost::create([
        'route_id' => $request->route_id,
        'commodity_id' => $request->commodity_id,
        'vehicle_type_id' => $request->vehicle_type_id??null,
        'container_type_id' => $request->container_type_id??null,
        'description' => $request->description,
        'is_container' => $request->is_container??0,
        'created_by' => auth()->id(),
        'cost' => 0,
      ]);
      if ($request->id) {
        $rc=RouteCostDetail::where('header_id', $request->id)->get();
        foreach ($rc as $key => $value) {
          RouteCostDetail::create([
            'header_id' => $r->id,
            'cost_type_id' => $value->cost_type_id,
            'created_by' => auth()->id(),
            'cost' => $value->cost,
            'total_liter' => $value->total_liter,
            'is_bbm' => $value->is_bbm,
            'is_internal' => $value->is_internal,
            'harga_satuan' => $value->harga_satuan,
            'description' => $value->description,
          ]);
        }
      }
      DB::commit();
    }
}
