<?php

namespace App\Http\Controllers\Api\v4\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\VehicleType;
use Response;
use DB;

class VehicleTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['message'] = 'OK';
      $data['data']=DB::table('vehicle_types')
      ->select('id', 'name')
      ->get();

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
        'name' => 'required',
        'type' => 'required'
      ], [
        'name.required' => 'Nama tidak boleh kosong',
        'type.required' => 'Tipe tidak boleh kosong',
      ]);
      DB::beginTransaction();
      VehicleType::create([
        'name' => $request->name,
        'type' => $request->type
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
        $data=VehicleType::find($id);
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
        'name' => 'required',
        'type' => 'required'
      ], [
        'name.required' => 'Nama tidak boleh kosong',
        'type.required' => 'Tipe tidak boleh kosong',
      ]);
      DB::beginTransaction();
      VehicleType::find($id)->update([
        'name' => $request->name,
        'type' => $request->type
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
      VehicleType::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }
}
