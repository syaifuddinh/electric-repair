<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\VehicleJoint;
use Response;
use DB;

class VehicleJointController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['index']=VehicleJoint::all();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
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
        'name' => 'required|unique:vehicle_joints,name',
        'tires' => 'required|integer'
      ]);
      DB::beginTransaction();
      VehicleJoint::create([
        'name' => $request->name,
        'tires' => $request->tires
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
      $data=VehicleJoint::find($id);
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
        'name' => 'required|unique:vehicle_joints,name,'.$id,
        'tires' => 'required|integer'
      ]);
      DB::beginTransaction();
      VehicleJoint::find($id)->update([
        'name' => $request->name,
        'tires' => $request->tires
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
      VehicleJoint::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }
}
