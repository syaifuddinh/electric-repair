<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Countries;
use App\Model\Port;
use App\Model\Vessel;
use App\Model\VoyageSchedule;
use App\Model\Container;
use Carbon\Carbon;
use DB;
use Response;
use App\Abstracts\Inventory\VoyageReceipt;
use App\Abstracts\Operational\VoyageSchedule AS VS;

class VoyageScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['ships']=Vessel::all();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 01-04-2020
      Description : Menampilkan daftar job order yang terikat dengan jadwal kapal terkait
      Developer : Didin
      Status : Create
    */
    public function voyageScheduleJobOrder($voyage_schedule_id) {
        $jobOrders = DB::table('job_orders AS J')
        ->join('job_order_details AS JD', 'JD.header_id', 'J.id')
        ->join('manifest_details AS MD', 'MD.job_order_detail_id', 'JD.id')
        ->join('manifests AS M', 'MD.header_id', 'M.id')
        ->join('containers AS C', 'M.container_id', 'C.id')
        ->join('voyage_schedules AS V', 'C.voyage_schedule_id', 'V.id')
        ->where('V.id', $voyage_schedule_id)
        ->select('J.id', 'J.invoice_id')
        ->groupBy('J.id')
        ->get();

        return $jobOrders;
    }

    /*
      Date : 01-04-2020
      Description : Mengubah tanggal invoice jika tanggal keberangkatan diubah
      Developer : Didin
      Status : Create
    */
    public function changeInvoiceDate($oldest_date, $newest_date, $voyage_schedule_id)
    {
        if($oldest_date != null) {
            $current_date = Carbon::parse($oldest_date);
            $diff = $current_date->diffInDays($newest_date);
            if($diff > 3 OR $diff < -3) {
                $jobOrders = $this->voyageScheduleJobOrder($voyage_schedule_id);
                foreach($jobOrders as $jobOrder) {
                    if($jobOrder->invoice_id != null) {
                        $invoice = DB::table('invoices')
                        ->whereId($jobOrder->invoice_id)
                        ->first();

                        DB::table('invoices')
                        ->whereId($jobOrder->invoice_id)
                        ->update([
                            'date_invoice' => Carbon::parse($newest_date)->format('Y-m-d'),
                            'status' => 2
                        ]);

                        if($invoice->journal_id != null) {
                            DB::table('journals')
                            ->whereId($invoice->journal_id)
                            ->update([
                                'status' => 2,
                                'date_transaction' => Carbon::parse($newest_date)->format('Y-m-d')
                            ]);
                        }
                    }
                }
            }
        }
    }
    
    /*
      Date : 31-03-2020
      Description : Menampilkan jadwal kapal
      Developer : Didin
      Status : Create
    */
    public function indexVoyageSchedule()
    {
        $data['voyage_schedule']=DB::table('voyage_schedules')
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

        'departure' => $request->filled('departure_date') ? createTimestamp($request->departure_date,$request->departure_time ?? '00:00') : null,
        'arrival' => $request->filled('arrival_date') ? createTimestamp($request->arrival_date,$request->arrival_time ?? '00:00') : null,

        'create_by' => auth()->id(),
      ]);
      DB::commit();

      return Response::json(['message' => 'Data berhasil di-input1']);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item'] = VS::show($id);
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
      $v = VoyageSchedule::find($id);

      $this->changeInvoiceDate($v->departure, ($request->filled('departure_date') ? Carbon::parse($request->departure_date)->format('Y-m-d') : null), $id);
      $v->update([
        'vessel_id' => $request->vessel_id,
        'pol_id' => $request->pol_id,
        'pod_id' => $request->pod_id,
        'countries_id' => $request->countries_id,
        'voyage' => $request->voyage,
        'etd' => createTimestamp($request->etd_date,$request->etd_time),
        'eta' => createTimestamp($request->eta_date,$request->eta_time),
        'departure' => $request->filled('departure_date') ? createTimestamp($request->departure_date,$request->departure_time ?? '00:00') : null,
        'arrival' => $request->filled('arrival_date') ? createTimestamp($request->arrival_date,$request->arrival_time ?? '00:00') : null
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

    /*
      Date : 25-03-2021
      Description : Menyimpan penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function storeReceipt(Request $request, $id)
    {
        $status_code = 200;
        $msg = 'Data successfully removed';
        DB::beginTransaction();
        try {
            $params = $request->all();
            VoyageReceipt::storeReceipt($params, $id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return Response::json($data, $status_code);
    }
}
