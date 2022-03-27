<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Service;
use App\Model\KpiStatus;
use App\Model\KpiLog;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class StatusProsesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
      $data['detail']=Service::with('service_type')->get();

      if($request->dt && $request->dt == true){
        $data = Service::with('service_type');

        return DataTables::of($data)
        ->make(true);
      }
      
      return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
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
        'sort_number' => 'required',
        'duration' => 'required',
        'service_id' => 'required',
        'status' => 'required',
      ]);
      DB::beginTransaction();
      $cek=KpiStatus::where('service_id', $request->service_id)->where('sort_number', $request->sort_number)->count();
      if ($cek>0) {
        return response()->json(['message' => 'Nomor Urut Sudah digunakan!'],500);
      }
      KpiStatus::create([
        'service_id' => $request->service_id,
        'sort_number' => $request->sort_number,
        'name' => $request->name,
        'duration' => $request->duration,
        'is_done' => $request->is_done,
        'status' => $request->status,
        'create_by' => auth()->id()
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
      $data['item']=Service::where('id', $id)->first();
      $data['detail_service']=KpiStatus::where('service_id', $id)->get();
      return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['item']=KpiStatus::find($id);
      return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
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
        'sort_number' => 'required',
        'duration' => 'required',
        'service_id' => 'required',
        'status' => 'required',
      ]);
      DB::beginTransaction();
      $cek=KpiStatus::where('service_id', $request->service_id)->where('sort_number', $request->sort_number)->where('id','!=',$id)->count();
      if ($cek>0) {
        return response()->json(['message' => 'Nomor Urut Sudah digunakan!'],500);
      }
      KpiStatus::find($id)->update([
        'sort_number' => $request->sort_number,
        'name' => $request->name,
        'duration' => $request->duration,
        'status' => $request->status,
        'is_done' => $request->is_done,
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
      KpiStatus::find($id)->delete();
      DB::commit();
      return response()->json(null);
    }
}
