<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Contact;
use App\Model\Service;
use App\Model\KpiStatus;
use App\Model\JobOrder;
use App\Model\KpiLog;
use App\User;
use DB;
use Response;
use Carbon\Carbon;

class ProgressController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['kpi_status']=DB::table('kpi_statuses')->leftJoin('services','services.id','=','kpi_statuses.service_id')->selectRaw('kpi_statuses.id,kpi_statuses.name, services.name as parent')->orderBy('kpi_statuses.service_id','asc')->orderBy('kpi_statuses.sort_number','asc')->get();
      $data['user']=User::select('id','name')->get();
      $data['service']=Service::with('service_type')->get();
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

    public function cari_status_by_jo($id)
    {
      $jo=JobOrder::find($id);
      $data['jo']=$jo;
      $data['kpi_status']=KpiStatus::where('service_id', $jo->service_id)->select('id','name')->orderBy('sort_number','asc')->get();
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
        'customer_id' => 'required',
        'job_order_id' => 'required',
        'date_update' => 'required',
        'kpi_status_id' => 'required',
        'description' => 'required'
      ]);

      DB::beginTransaction();
      $file=$request->file('file');
      $jo=JobOrder::find($request->job_order_id);
      $klog=KpiLog::create([
        'kpi_status_id' => str_replace("number:","",$request->kpi_status_id),
        'job_order_id' => str_replace("number:","",$request->job_order_id),
        'date_update' => Carbon::parse($request->date_update),
        'create_by' => auth()->id(),
        'company_id' => $jo->company_id,
        'description' => $request->description,
      ]);

      if (isset($file)) {
        $filename="JOBORDER_".$jo->id."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();
        KpiLog::find($klog->id)->update([
          'file_name' => 'files/'.$filename,
          'extension' => $file->getClientOriginalExtension()
        ]);
        $file->move(public_path('files'), $filename);
      }
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
