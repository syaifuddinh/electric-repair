<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\CustomerStage;
use App\Model\Contact;
use App\Model\Company;
use App\User;
use App\Model\Inquery;
use App\Model\InqueryActivity;
use App\Model\Quotation;
use App\Model\Lead;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;

class OpportunityController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['customer_stage']=CustomerStage::all();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['company']=companyAdmin(auth()->id());
      $data['stage']=CustomerStage::all();
      $data['service_group']=DB::table('service_groups')->selectRaw('id,name')->get();

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
        'date_opportunity' => 'required',
        // 'company_id' => 'required',
        'customer_id' => 'required',
        'customer_stage_id' => 'required',
        'sales_id' => 'required',
      ]);
      DB::beginTransaction();
      $con=Contact::find($request->customer_id);

      $code = new TransactionCode(auth()->user()->company_id, 'opportunity');
      $code->setCode();
      $trx_code = $code->getCode();

      $i=Inquery::create([
        'company_id' => auth()->user()->company_id,
        'customer_id' => $request->customer_id,
        'sales_opportunity_id' => $request->sales_id,
        'customer_stage_id' => $request->customer_stage_id,
        'code_opportunity' => $trx_code,
        'create_by' => auth()->id(),
        'date_opportunity' => dateDB($request->date_opportunity),
        'description_opportunity' => $request->description_opportunity,
        'interest' => implode(",",$request->interest)
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
      $data['item']=Inquery::with('company','sales_opportunity','customer')->where('id', $id)->first();
      $data['detail']=InqueryActivity::with('sales','customer_stage')->where('header_id', $id)->get();
      $data['service_group']=DB::table('service_groups')->selectRaw('id,name')->get();
      $data['stage']=CustomerStage::all();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function data_activity($id)
    {
      $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address')->get();
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
      $data['stage']=CustomerStage::all();
      $data['item']=Inquery::find($id);
      $data['service_group']=DB::table('service_groups')->selectRaw('id,name')->get();

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
        'date_opportunity' => 'required',
        // 'company_id' => 'required',
        'customer_id' => 'required',
        'customer_stage_id' => 'required',
        'sales_id' => 'required',
      ]);
      DB::beginTransaction();
      $interest = $request->interest;
      $interest = is_array($interest) ? implode(",",$request->interest) : $interest;
      $i=Inquery::find($id)->update([
        'customer_id' => $request->customer_id,
        'sales_opportunity_id' => $request->sales_id,
        'customer_stage_id' => $request->customer_stage_id,
        'date_opportunity' => dateDB($request->date_opportunity),
        'description_opportunity' => $request->description_opportunity,
        'interest' => $interest
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
      return Response::json(null,200);
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
        $filename="OPPORTUNITY_".$id."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();
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
        'description' => ($request->description?:null),
        'is_opportunity' => 1,
        'file_name' => ($filename?'files/'.$filename:null),
        'extension' => ($filename?$file->getClientOriginalExtension():null),
        'create_by' => auth()->id(),
      ]);

      DB::commit();
      return Response::json(null);
    }

    public function done_activity($id)
    {
      DB::beginTransaction();
      InqueryActivity::find($id)->update([
        'is_done' => 1,
        'date_done' => Carbon::now()
      ]);
      DB::commit();
    }

    public function delete_activity($id)
    {
      DB::beginTransaction();
      InqueryActivity::find($id)->delete();
      DB::commit();
      return Response::json(null);
    }

    public function cancel_opportunity($id)
    {
      DB::beginTransaction();
      $in=Inquery::find($id)->update([
        'status' => 5,
        'cancel_opportunity_by' => auth()->id(),
        'cancel_opportunity_date' => Carbon::now()
      ]);
      Lead::where('inquery_id', $id)->update([
        'step' => 7
      ]);
      DB::commit();
    }
    public function cancel_inquery($id)
    {
      DB::beginTransaction();
      $in=Inquery::find($id)->update([
        'status' => 6,
        'cancel_inquery_by' => auth()->id(),
        'cancel_inquery_date' => Carbon::now()
      ]);
      Lead::where('inquery_id', $id)->update([
        'step' => 8
      ]);
      DB::commit();
    }
    public function cancel_cancel_opportunity($id)
    {
      DB::beginTransaction();
      $in=Inquery::find($id)->update([
        'status' => 1,
      ]);
      Lead::where('inquery_id', $id)->update([
        'step' => 2
      ]);
      DB::commit();
    }
    public function cancel_cancel_inquery($id)
    {
      DB::beginTransaction();
      $in=Inquery::find($id)->update([
        'status' => 2,
      ]);
      Lead::where('inquery_id', $id)->update([
        'step' => 3
      ]);
      DB::commit();
    }
}
