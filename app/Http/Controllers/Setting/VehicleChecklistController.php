<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\VehicleChecklist;
use Illuminate\Support\Facades\DB;

class VehicleChecklistController extends Controller
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
        'name' => 'required|unique:vehicle_checklists,name',
        'is_active' => 'required',
      ]);

      DB::beginTransaction();
      VehicleChecklist::create([
        'name' => $request->name,
        'is_active' => $request->is_active,
      ]);
      DB::commit();

      return response()->json(null);
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
      $data=VehicleChecklist::find($id);
      return response()->json($data,200,[],JSON_NUMERIC_CHECK);

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
        'name' => 'required|unique:vehicle_checklists,name,'.$id,
        'is_active' => 'required',
      ]);

      DB::beginTransaction();
      VehicleChecklist::find($id)->update([
        'name' => $request->name,
        'is_active' => ($request->is_active == 'true' || $request->is_active == 1) ? 1:0,
      ]);
      DB::commit();

      return response()->json(null);
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
      VehicleChecklist::find($id)->delete();
      DB::commit();

      return response()->json(null);
    }
}
