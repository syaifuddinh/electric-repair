<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\VehicleMaintenanceType;
use Response;
use DB;

class MaintenanceTypeController extends Controller
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
        'name' => 'required|unique:vehicle_maintenance_types,name',
        'type' => 'required',
        'interval' => 'required|integer|min:1',
        'cost' => 'required|integer',
        'is_repeat' => 'required',
      ]);

      DB::beginTransaction();
      VehicleMaintenanceType::create([
        'name' => $request->name,
        'type' => $request->type,
        'interval' => $request->interval,
        'cost' => $request->cost,
        'is_repeat' => $request->is_repeat,
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
      $item=VehicleMaintenanceType::find($id);
      return Response::json($item,200,[],JSON_NUMERIC_CHECK);
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
        'name' => 'required|unique:vehicle_maintenance_types,name,'.$id,
        'type' => 'required',
        'interval' => 'required|integer|min:1',
        'cost' => 'required|integer',
        'is_repeat' => 'required',
      ]);

      DB::beginTransaction();
      VehicleMaintenanceType::find($id)->update([
        'name' => $request->name,
        'type' => $request->type,
        'interval' => $request->interval,
        'cost' => $request->cost,
        'is_repeat' => $request->is_repeat,
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
      VehicleMaintenanceType::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }
}
