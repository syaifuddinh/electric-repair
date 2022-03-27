<?php

namespace App\Http\Controllers\Vehicle;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Company;
use App\Model\VehicleDistance;
use App\Model\Vehicle;
use Response;
use DB;

class VehicleDistanceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['companies'] = Company::all();
      $data['vehicles'] = Vehicle::all();

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
        'vehicle_id' => 'required',
        'date_distance' => 'required',
        'distance' => 'required|integer|min:1',
      ]);

      DB::beginTransaction();
      VehicleDistance::create([
        'vehicle_id' => $request->vehicle_id,
        'date_distance' => dateDB($request->date_distance),
        'distance' => $request->distance,
        'create_by' => auth()->id(),
      ]);

      Vehicle::find($request->vehicle_id)->update([
        'last_km' => DB::raw('last_km+'.$request->distance),
        'last_km_date' => dateDB($request->distance)
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
        //
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
        //
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
}
