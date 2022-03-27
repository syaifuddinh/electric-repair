<?php

namespace App\Http\Controllers\Api\v4\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\VoyageSchedule;
use App\Model\Container;
use App\Model\ContainerType;
use App\Model\Commodity;
use App\Model\Company;
use DB;
use Response;

class ContainerController extends Controller
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
      $data['company']=companyAdmin(auth()->id());
      $data['commodity']=Commodity::all();
      $data['voyage_schedule']=VoyageSchedule::all();
      $data['container_type']=ContainerType::all();

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
        'voyage_schedule_id' => 'required',
        'company_id' => 'required',
        'container_no' => 'required',
        'container_type_id' => 'required',
        'commodity_id' => 'required',
        'commodity' => 'required',
        // 'stripping_time' => 'required_if:stripping_date,',
        // 'stuffing_time' => 'required_if:stuffing_date,',
      ]);

      DB::beginTransaction();
      $vs=VoyageSchedule::find($request->voyage_schedule_id);

      if (isset($request->stripping_date) && isset($request->stripping_time)) {
        $stripping=createTimestamp($request->stripping_date,$request->stripping_time);
      }
      if (isset($request->stuffing_date) && isset($request->stuffing_time)) {
        $stuffing=createTimestamp($request->stuffing_date,$request->stuffing_time);
      }

      Container::create([
        'container_type_id' => $request->container_type_id,
        'vessel_id' => $vs->vessel_id,
        'company_id' => $request->company_id,
        'commodity_id' => $request->commodity_id,
        'voyage_schedule_id' => $request->voyage_schedule_id,
        'container_no' => $request->container_no,
        'booking_date' => dateDB($request->booking_date),
        'booking_number' => $request->booking_number,
        'departure' => $vs->departure,
        'arrival' => $vs->arrival,
        'seal_no' => $request->seal_no,
        'is_fcl' => $request->is_fcl,
        'commodity' => $request->commodity,
        'create_by' => auth()->id(),
        'stripping' => $stripping??null,
        'stuffing' => $stuffing??null,
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
      $data['item']=Container::with('company','voyage_schedule','voyage_schedule.vessel','container_type')->where('id', $id)->first();
      return Response::json($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['company']=companyAdmin(auth()->id());
      $data['commodity']=Commodity::all();
      $data['voyage_schedule']=VoyageSchedule::all();
      $data['container_type']=ContainerType::all();
      $data['item']=Container::find($id);

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
        'voyage_schedule_id' => 'required',
        'company_id' => 'required',
        'container_no' => 'required',
        'container_type_id' => 'required',
        'commodity_id' => 'required',
        'commodity' => 'required',
        // 'stripping_time' => 'required_if:stripping_date',
        // 'stuffing_time' => 'required_if:stuffing_date',
      ]);
      DB::beginTransaction();
      $vs=VoyageSchedule::find($request->voyage_schedule_id);
      if (isset($request->stripping_date) && isset($request->stripping_time)) {
        $stripping=createTimestamp($request->stripping_date,$request->stripping_time);
      }
      if (isset($request->stuffing_date) && isset($request->stuffing_time)) {
        $stuffing=createTimestamp($request->stuffing_date,$request->stuffing_time);
      }
      Container::find($id)->update([
        'container_type_id' => $request->container_type_id,
        'vessel_id' => $vs->vessel_id,
        'company_id' => $request->company_id,
        'commodity_id' => $request->commodity_id,
        'voyage_schedule_id' => $request->voyage_schedule_id,
        'container_no' => $request->container_no,
        'booking_date' => dateDB($request->booking_date),
        'booking_number' => $request->booking_number,
        'departure' => $vs->departure,
        'arrival' => $vs->arrival,
        'seal_no' => $request->seal_no,
        'is_fcl' => $request->is_fcl,
        'commodity' => $request->commodity,
        'stripping' => $stripping??null,
        'stuffing' => $stuffing??null,
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
        //
    }
}
