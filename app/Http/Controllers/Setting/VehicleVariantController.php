<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\VehicleVariant;
use App\Model\VehicleType;
use App\Model\VehicleManufacturer;
use App\Model\BbmType;
use App\Model\VehicleJoint;
use App\Model\TireSize;
use DB;
use Response;

class VehicleVariantController extends Controller
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
      $data['vehicle_manufacturer']=VehicleManufacturer::all();
      $data['bbm_type']=BbmType::all();
      $data['vehicle_joint']=VehicleJoint::all();
      $data['tire_size']=TireSize::all();

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
        'code' => 'required|unique:vehicle_variants',
        'name' => 'required',
        'vehicle_type_id' => 'required',
        'vehicle_manufacturer_id' => 'required',
        'year_manufacture' => 'required|integer',
        'cost' => 'required|integer',
        'cylinder' => 'required|integer',
        'cc_capacity' => 'required|integer',
        'bbm_type_id' => 'required',
        'bbm_capacity' => 'required|integer',
        'transmission' => 'required',
        'joints' => 'required|integer',
        'vehicle_joint_id' => 'required',
        'tire_size_id' => 'required',
        'seat' => 'required|integer',
        'first_km_initial' => 'required|integer',
        'next_km_initial' => 'required|integer',
      ]);
      DB::beginTransaction();
      VehicleVariant::create([
        'code' => $request->code,
        'name' => $request->name,
        'vehicle_type_id' => $request->vehicle_type_id,
        'year_manufacture' => $request->year_manufacture,
        'cost' => $request->cost,
        'cylinder' => $request->cylinder,
        'cc_capacity' => $request->cc_capacity,
        'bbm_type_id' => $request->bbm_type_id,
        'bbm_capacity' => $request->bbm_capacity,
        'transmission' => $request->transmission,
        'joints' => $request->joints,
        'vehicle_joint_id' => $request->vehicle_joint_id,
        'tire_size_id' => $request->tire_size_id,
        'seat' => $request->seat,
        'first_km_initial' => $request->first_km_initial,
        'next_km_initial' => $request->next_km_initial,
        'vehicle_manufacturer_id' => $request->vehicle_manufacturer_id,
        'description' => $request->description,
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['vehicle_type']=VehicleType::all();
      $data['vehicle_manufacturer']=VehicleManufacturer::all();
      $data['bbm_type']=BbmType::all();
      $data['vehicle_joint']=VehicleJoint::all();
      $data['tire_size']=TireSize::all();
      $data['item']=VehicleVariant::find($id);

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
        'code' => 'required|unique:vehicle_variants,code,'.$id,
        'name' => 'required',
        'vehicle_type_id' => 'required',
        'vehicle_manufacturer_id' => 'required',
        'year_manufacture' => 'required|integer',
        'cost' => 'required|integer',
        'cylinder' => 'required|integer',
        'cc_capacity' => 'required|integer',
        'bbm_type_id' => 'required',
        'bbm_capacity' => 'required|integer',
        'transmission' => 'required',
        'joints' => 'required|integer',
        'vehicle_joint_id' => 'required',
        'tire_size_id' => 'required',
        'seat' => 'required|integer',
        'first_km_initial' => 'required|integer',
        'next_km_initial' => 'required|integer',
      ]);
      DB::beginTransaction();
      VehicleVariant::find($id)->update([
        'code' => $request->code,
        'name' => $request->name,
        'vehicle_type_id' => $request->vehicle_type_id,
        'year_manufacture' => $request->year_manufacture,
        'cost' => $request->cost,
        'cylinder' => $request->cylinder,
        'cc_capacity' => $request->cc_capacity,
        'bbm_type_id' => $request->bbm_type_id,
        'bbm_capacity' => $request->bbm_capacity,
        'transmission' => $request->transmission,
        'joints' => $request->joints,
        'vehicle_joint_id' => $request->vehicle_joint_id,
        'tire_size_id' => $request->tire_size_id,
        'seat' => $request->seat,
        'first_km_initial' => $request->first_km_initial,
        'next_km_initial' => $request->next_km_initial,
        'vehicle_manufacturer_id' => $request->vehicle_manufacturer_id,
        'description' => $request->description,
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
      VehicleVariant::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }
}
