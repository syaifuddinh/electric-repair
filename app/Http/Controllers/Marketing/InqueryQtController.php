<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\CustomerStage;
use App\Model\Contact;
use App\Model\Company;
use App\User;
use App\Model\Inquery;
use App\Model\Lead;
use App\Model\InqueryActivity;
use App\Utils\TransactionCode;
use DB;
use Response;

class InqueryQtController extends Controller
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

      $data['opportunity']=Inquery::where('opportunity_id', null)
                                ->whereRaw('status < 4')
                                ->select('id','code_opportunity as name','customer_id')
                                ->get();

      $data['stage']=CustomerStage::all();
      $data['prospect']=CustomerStage::where('is_prospect', 1)->first();

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
        'date_inquery' => 'required',
        // 'company_id' => 'required',
        'customer_id' => 'required',
        'customer_stage_id' => 'required',
        'sales_id' => 'required',
        'type_send' => 'required'
      ]);
      DB::beginTransaction();
      $con=Contact::find($request->customer_id);

      $code = new TransactionCode(auth()->user()->company_id, 'inquery');
      $code->setCode();
      $trx_code = $code->getCode();

      if (empty($request->opportunity_id)) {
        $i=Inquery::create([
          'company_id' => auth()->user()->company_id,
          'customer_id' => $request->customer_id,
          'sales_inquery_id' => $request->sales_id,
          'customer_stage_id' => $request->customer_stage_id,
          'code_inquery' => $trx_code,
          'create_by' => auth()->id(),
          'inquery_by' => auth()->id(),
          'status' => 2,
          'type_send' => $request->type_send,
          'date_inquery' => dateDB($request->date_inquery),
          'description_inquery' => $request->description_inquery,
        ]);
      } else {
        $i=Inquery::find($request->opportunity_id);
        $i->update([
          'sales_inquery_id' => $request->sales_id,
          'customer_stage_id' => $request->customer_stage_id,
          'code_inquery' => $trx_code,
          'inquery_by' => auth()->id(),
          'status' => 2,
          'type_send' => $request->type_send,
          'date_inquery' => dateDB($request->date_inquery),
          'description_inquery' => $request->description_inquery,
        ]);

        if(isset($i->lead_id)) {
          Lead::find($i->lead_id)->update([
            'step' => 3
          ]);
        }
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
      $data['item']=Inquery::with('company','sales_inquery','customer','customer_stage')->where('id', $id)->first();
      $data['detail']=InqueryActivity::with('sales','customer_stage')->where('header_id', $id)->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function data_activity($id)
    {
      $data['sales']=Contact::whereRaw("is_sales = 1")->select('id','name')->get();
      $data['stage']=CustomerStage::all();

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
      $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address')->get();
      $data['sales']=Contact::whereRaw("is_sales = 1")->select('id','name')->get();
      $data['company']=companyAdmin(auth()->id());
      $data['opportunity']=Inquery::where('opportunity_id', null)->select('id','code_opportunity as name')->get();
      $data['stage']=CustomerStage::all();
      $data['item']=Inquery::find($id);

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
        'date_inquery' => 'required',
        // 'company_id' => 'required',
        'customer_id' => 'required',
        'customer_stage_id' => 'required',
        'sales_id' => 'required',
        'type_send' => 'required'
      ]);
      DB::beginTransaction();

      $con=Contact::find($request->customer_id);
      $i=Inquery::find($id)->update([
        // 'company_id' => $con->company_id,
        'customer_id' => $request->customer_id,
        'sales_inquery_id' => $request->sales_id,
        'customer_stage_id' => $request->customer_stage_id,
        'type_send' => $request->type_send,
        'date_inquery' => dateDB($request->date_inquery),
        'description_inquery' => $request->description_inquery,
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
      Inquery::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function store_activity(Request $request, $id)
    {
      $request->validate([
        'sales_id' => 'required',
        'date_activity' => 'required',
        'customer_stage_id' => 'required',
      ]);

      DB::beginTransaction();
      if ($request->file('file')) {
        $file=$request->file('file');
        $filename="INQUERY_".$id."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();
        $file->move(public_path('files'), $filename);
      } else {
        $filename=null;
      }

      $i=Inquery::find($id);
      InqueryActivity::create([
        'header_id' => $id,
        'sales_id' => str_replace("number:","",$request->sales_id),
        'customer_stage_id' => str_replace("number:","",$request->customer_stage_id),
        'date_activity' => dateDB($request->date_activity),
        'description' => $request->description,
        'is_opportunity' => 0,
        'file_name' => ($filename?'files/'.$filename:null),
        'extension' => ($filename?$file->getClientOriginalExtension():null),
        'create_by' => auth()->id(),
      ]);

      DB::commit();
      return Response::json(null);
    }

    public function cari_oppo($id)
    {
      $data=Inquery::with('customer')
          ->find($id);
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
    }

    public function findByCustomer($customerId)
    {
        $oportunity = Inquery::where('customer_id', $customerId)
            ->whereRaw('status < 4')
            ->select('id','code_opportunity as name','customer_id')
            ->get();

        return response()->json($oportunity);
    }
}
