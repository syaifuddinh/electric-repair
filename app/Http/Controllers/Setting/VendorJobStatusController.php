<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use DB;
use Illuminate\Support\Str;

class VendorJobStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = DB::table('vendor_job_statuses')
        ->select('id', 'name')
        ->orderBy('priority', 'asc')
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
        'priority' => 'required',
        'name' => 'required'
      ]);
      DB::beginTransaction();

      $slug = Str::snake($request->name);
      DB::table('vendor_job_statuses')
      ->insert([
            'name' => $request->name,
            'editable' => 1,
            'priority' => $request->priority,
            'slug' => $slug
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
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
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
        'priority' => 'required',
        'name' => 'required'
      ]);
      DB::beginTransaction();

      DB::table('vendor_job_statuses')
      ->whereId($id)
      ->update([
            'name' => $request->name,
            'priority' => $request->priority
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
      DB::table('vendor_job_statuses')->whereId($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function store_cost(Request $request, $id=null)
    {
      // dd($request);
      if (empty($id)) {
        RouteCost::create([
          'route_id' => $request->header_id,
          'commodity_id' => $request->commodity_id,
          'cost' => 0,
          'description' => $request->description,
          'vehicle_type_id' => $request->vehicle_type_id,
          'container_type_id' => $request->container_type_id,
          'is_container' => $request->is_container,
          'created_by' => auth()->id(),
        ]);
      } else {
        RouteCost::find($id)->update([
          // 'route_id' => $request->header_id,
          'commodity_id' => $request->commodity_id,
          // 'cost' => 0,
          'description' => $request->description,
          'vehicle_type_id' => $request->vehicle_type_id,
          'container_type_id' => $request->container_type_id,
          'is_container' => $request->is_container,
        ]);
      }

      return Response::json(null);
    }

    public function delete_cost($id)
    {
      DB::beginTransaction();
      RouteCost::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function cost($id)
    {
      $data['item']=RouteCost::find($id);
      $data['cost_type']=CostType::with('parent')->where('is_invoice', 0)->where('company_id', $data['item']->trayek->company_id)->where('parent_id', '!=', null)->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_detail_cost(Request $request, $id)
    {
      $request->validate([
        'cost_type_id' => 'required',
        'cost' => 'required',
        'is_internal' => 'required',
      ]);
      DB::beginTransaction();
      $ct=CostType::find($request->cost_type_id['id']);
      RouteCostDetail::create([
        'header_id' => $id,
        'cost_type_id' => $request->cost_type_id['id'],
        'created_by' => auth()->id(),
        'cost' => $request->cost,
        'is_bbm' => $ct->is_bbm,
        'is_internal' => $request->is_internal,
        'description' => $request->description,
        'harga_satuan' => ($request->harga_satuan?:0),
        'total_liter' => ($request->total_liter?:1),
      ]);

      // RouteCost::find($id)->update([
      //   'cost' => RouteCost::find($id)->details->sum('cost')
      // ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_detail_cost($id)
    {
      DB::beginTransaction();
      $rtc=RouteCostDetail::find($id);
      RouteCostDetail::find($id)->delete();

      // RouteCost::find($rtc->header_id)->update([
      //   'cost' => RouteCost::find($rtc->header_id)->details->sum('cost')
      // ]);
      DB::commit();

      return Response::json(null);
    }
}
