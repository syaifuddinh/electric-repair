<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\VoyageSchedule;
use App\Model\Container;
use App\Abstracts\Operational\Container AS C;
use App\Model\ContainerType;
use App\Model\Commodity;
use App\Model\Company;
use App\Abstracts\Operational\JobOrderContainer;
use Carbon\Carbon;
use DB;
use Response;
use Exception;

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
      $data['commodity']=Commodity::all();
      $data['voyage_schedule']=VoyageSchedule::all();

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
        $request->validate([
            'company_id' => 'required',
            'container_no' => 'required',
            'container_type_id' => 'required',
            'commodity_id' => 'required',
            'commodity' => 'required',
        ]);

        DB::beginTransaction();
        $vs=VoyageSchedule::find($request->voyage_schedule_id);

        if (isset($request->stripping_date) && isset($request->stripping_time)) {
            $stripping = createTimestamp($request->stripping_date,$request->stripping_time);
        }
        if (isset($request->stuffing_date) && isset($request->stuffing_time)) {
            $stuffing = createTimestamp($request->stuffing_date,$request->stuffing_time);
        }

        $c = Container::create([
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

        if($request->job_order_id) {
            JobOrderContainer::store($c->id, $request->job_order_id);
        }
        DB::commit();

        return Response::json(['message' => 'Data berhasil di-input'] );
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
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['item']=Container::find($id);

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 31-03-2020
      Description : Memeriksa apakah jadwal kapal sudah mempunyai invoice 
                    atau belum
      Developer : Didin
      Status : Create
    */
    public function jobOrderContainer($id)
    {
        $jobOrder = DB::table('containers AS C')
        ->join('manifests AS M', 'M.container_id', 'C.id')
        ->join('manifest_details AS MD', 'MD.header_id', 'M.id')
        ->join('job_order_details AS JD', 'JD.id', 'MD.job_order_detail_id')
        ->join('job_orders AS J', 'J.id', 'JD.header_id')
        ->join('voyage_schedules AS V', 'V.id', 'C.voyage_schedule_id')
        ->where('C.id', $id)
        ->select('J.invoice_id', 'V.voyage', 'V.departure', 'V.arrival')
        ->get();

        return $jobOrder;      
    }
    /*
      Date : 31-03-2020
      Description : Memeriksa apakah jadwal kapal sudah mempunyai invoice 
                    atau belum
      Developer : Didin
      Status : Create
    */
    public function hasInvoice($id)
    {
      $has_invoice = 0;
      $jobOrders = $this->jobOrderContainer($id);

      foreach ($jobOrders AS $jobOrder) {
          if($jobOrder->invoice_id != null) {
              $has_invoice = 1;
          }
      }

      return $has_invoice;;
    }

    /*
      Date : 31-03-2020
      Description : Memeriksa apakah jadwal kapal sudah mempunyai invoice 
                    atau belum. Output berupa JSON
      Developer : Didin
      Status : Create
    */
    public function checkInvoice($id)
    {
      
      $data['has_invoice'] = $this->hasInvoice($id);
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 31-03-2020
      Description : Meng-update container
      Developer : Didin
      Status : Edit
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
      $c = Container::find($id);
      $current_voyage_schedule_id = $c->voyage_schedule_id;
      $c->update([
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

      if($current_voyage_schedule_id != $request->voyage_schedule_id) {
          $has_invoice = $this->hasInvoice($id);
          if($has_invoice == 1) {
              $jobOrders = $this->jobOrderContainer($id);
              foreach($jobOrders as $jobOrder) {
                  if($jobOrder->invoice_id != null) {
                      if($jobOrder->departure == null) {
                          return Response::json(['message' => 'Waktu keberangkatan pada kapal ' . $jobOrder->voyage . ' masih kosong'], 421);
                      }
                      $invoice = DB::table('invoices')
                      ->whereId($jobOrder->invoice_id)
                      ->first();
                      if($invoice->status > 2) {
                          DB::table('invoices')
                          ->whereId($invoice->id)
                          ->update([
                              'date_invoice' => Carbon::parse($jobOrder->departure)->format('Y-m-d'),
                              'journal_id' => null,
                              'status' => 2
                          ]);
                          DB::table('journal_details')
                          ->whereHeaderId($invoice->journal_id)
                          ->delete();
                          DB::table('journals')
                          ->whereId($invoice->journal_id)
                          ->delete();
                          
                      } else {
                          DB::table('invoices')
                          ->whereId($invoice->id)
                          ->update([
                              'date_invoice' => Carbon::parse($jobOrder->departure)->format('Y-m-d'),
                              'status' => 2
                          ]);                        
                      }

                  }
              }
          } 
      }
      DB::commit();

      return Response::json(null);
    }

    /*
      Date : 07-03-2021
      Description : Menghapus data
      Developer : Didin
      Status : Create
    */
    public function destroy($id)
    {
        $statusCode = 200;
        $msg = 'Data successfully deleted';
        try {
            C::destroy($id);
        } catch(Exception $e) {
            $statusCode = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;
        return response()->json($data, $statusCode);
    }
}
