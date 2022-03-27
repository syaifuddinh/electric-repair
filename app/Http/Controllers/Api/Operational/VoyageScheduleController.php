<?php

namespace App\Http\Controllers\Api\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Countries;
use App\Model\Port;
use App\Model\Vessel;
use App\Model\VoyageSchedule;
use App\Model\Container;
use DB;
use Response;

class VoyageScheduleController extends Controller
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
      $data['port']=Port::all();
      $data['vessel']=Vessel::all();
      $data['countries']=Countries::all();
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
        'countries_id' => 'required',
        'vessel_id' => 'required',
        'voyage' => 'required|unique:voyage_schedules,voyage',
        'pol_id' => 'required',
        'pod_id' => 'required',
        'etd_date' => 'required',
        'eta_date' => 'required',
        'etd_time' => 'required',
        'eta_time' => 'required',
      ]);

      DB::beginTransaction();
      VoyageSchedule::create([
        'countries_id' => $request->countries_id,
        'vessel_id' => $request->vessel_id,
        'pol_id' => $request->pol_id,
        'pod_id' => $request->pod_id,
        'voyage' => $request->voyage,
        'total_container' => 0,
        'etd' => createTimestamp($request->etd_date,$request->etd_time),
        'eta' => createTimestamp($request->eta_date,$request->eta_time),
        'create_by' => auth()->id(),
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
      $data['item']=VoyageSchedule::with('vessel','pol','pod', 'countries', 'created_by')->where('id', $id)->first();
      $data['detail']=Container::with('container_type')->where('voyage_schedule_id', $id)->get();
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
      $data['port']=Port::all();
      $data['vessel']=Vessel::all();
      $data['countries']=Countries::all();
      $data['item']=VoyageSchedule::find($id);
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
        'vessel_id' => 'required',
        'voyage' => 'required|unique:voyage_schedules,voyage,'.$id,
        'pol_id' => 'required',
        'pod_id' => 'required',
        'countries_id' => 'required',
        'etd_date' => 'required',
        'eta_date' => 'required',
        'etd_time' => 'required',
        'eta_time' => 'required',
      ]);

      DB::beginTransaction();
      VoyageSchedule::find($id)->update([
        'vessel_id' => $request->vessel_id,
        'pol_id' => $request->pol_id,
        'pod_id' => $request->pod_id,
        'countries_id' => $request->countries_id,
        'voyage' => $request->voyage,
        'etd' => createTimestamp($request->etd_date,$request->etd_time),
        'eta' => createTimestamp($request->eta_date,$request->eta_time),
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
      VoyageSchedule::find($id)->delete();
      DB::commit();
      return Response::json(null);
    }
}
