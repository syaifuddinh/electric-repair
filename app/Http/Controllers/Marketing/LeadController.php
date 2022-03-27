<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Contact;
use App\Model\Company;
use App\Model\City;
use App\Model\Industry;
use App\Model\LeadStatus;
use App\Model\LeadSource;
use App\Model\LeadActivity;
use App\Model\LeadDocument;
use App\Model\Lead;
use App\Model\Inquery;
use App\Model\Quotation;
use App\Utils\TransactionCode;
use Carbon\Carbon;
use Response;
use DB;

class LeadController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $data['source'] = LeadSource::all();
      $data['status'] = LeadStatus::all();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['city'] = City::all();
      $data['industry'] = Industry::all();
      $data['source'] = LeadSource::all();
      $data['status'] = LeadStatus::all();
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
        'name' => 'required',
        'address' => 'required',
        'city_id' => 'required',
        'sales_id' => 'required',
        'lead_status_id' => 'required',
        'lead_source_id' => 'required',
        'lead_industry_id' => 'required',
        'email' => 'required|email|unique:contacts,email',
      ]);

      DB::beginTransaction();
      $l=Lead::create([
        'address' => $request->address,
        'city_id' => $request->city_id,
        'company_id' => $request->company_id,
        'contact_person' => $request->contact_person,
        'contact_person_email' => $request->contact_person_email,
        'contact_person_phone' => $request->contact_person_no,
        'email' => $request->email,
        'name' => $request->name,
        'phone' => $request->phone,
        'phone2' => $request->phone2,
        'postal_code' => $request->postal_code,
        'sales_id' => $request->sales_id,
        'lead_status_id' => $request->lead_status_id,
        'lead_source_id' => $request->lead_source_id,
        'industry_id' => $request->lead_industry_id,
        'create_by' => auth()->id()
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
      $data['item']=Lead::with('industry','lead_status','lead_source','sales','city','company')->where('id', $id)->first();
      $data['activity']=LeadActivity::with('creates')->where('header_id', $id)->orderBy('created_at','desc')->get();
      $data['lead_status']=LeadStatus::all();
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
      $data['item'] = Lead::find($id);
      $data['company'] = companyAdmin(auth()->id());
      $data['city'] = City::all();
      $data['industry'] = Industry::all();
      $data['source'] = LeadSource::all();
      $data['status'] = LeadStatus::all();
      $data['sales'] = Contact::whereRaw("is_sales = 1")->get();
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
        'name' => 'required',
        'address' => 'required',
        'city_id' => 'required',
        'sales_id' => 'required',
        'lead_status_id' => 'required',
        'lead_source_id' => 'required',
        'lead_industry_id' => 'required',
        'email' => 'required|email|unique:leads,email,'.$id,
      ]);

      DB::beginTransaction();
      $l=Lead::find($id)->update([
        'address' => $request->address,
        'city_id' => $request->city_id,
        'company_id' => $request->company_id,
        'contact_person' => $request->contact_person,
        'contact_person_email' => $request->contact_person_email,
        'contact_person_phone' => $request->contact_person_no,
        'email' => $request->email,
        'name' => $request->name,
        'phone' => $request->phone,
        'phone2' => $request->phone2,
        'postal_code' => $request->postal_code,
        'sales_id' => $request->sales_id,
        'lead_status_id' => $request->lead_status_id,
        'lead_source_id' => $request->lead_source_id,
        'industry_id' => $request->lead_industry_id,
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
      Lead::find($id)->delete();
      DB::commit();
    }

    public function store_activity(Request $request ,$id)
    {
      $request->validate([
        'name' => 'required',
        'date_activity' => 'required',
        'description' => 'required',
      ]);
      DB::beginTransaction();
      LeadActivity::create([
        'header_id' => $id,
        'name' => $request->name,
        'description' => $request->description,
        'date_activity' => dateDB($request->date_activity),
        'create_by' => auth()->id()
      ]);
      DB::commit();
      return Response::json(null);
    }

    public function store_document(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'file' => 'required',
        'name' => 'required'
      ]);
      $file=$request->file('file');
      $filename="LEAD_".$id."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();

      DB::beginTransaction();
      LeadDocument::create([
        'header_id' => $id,
        'file_name' => 'files/'.$filename,
        // 'date_upload' => Carbon::now(),
        'extension' => $file->getClientOriginalExtension(),
        'name' => $request->name,
        'create_by' => auth()->id()
      ]);
      DB::commit();
      $file->move(public_path('files'), $filename);

      return Response::json(null);
    }

    // delete document pada menu pemasaran->lead
    public function delete_document(Request $request, $id)
    {
      DB::table('lead_documents')->where('id', '=', $id)->delete();

      return Response::json(null);
    }

    public function document($id)
    {
      $data['detail']=LeadDocument::where('header_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
    public function change_status(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'lead_status_id' => 'required',
        'is_activity' => 'required',
        'name' => 'required_if:is_activity,1',
        'plan_date' => 'required_if:is_activity,1',
        'plan_time' => 'required_if:is_activity,1',
        'description' => 'required_if:is_activity,1',
      ]);
      DB::beginTransaction();
      $l=Lead::find($id);
      if ($l->lead_status_id==$request->lead_status_id) {
        return Response::json(['message' => 'Status ini telah digunakan!'],500);
      }
      $l->update([
        'lead_status_id' => $request->lead_status_id
      ]);
      if ($request->is_activity) {
        LeadActivity::create([
          'header_id' => $id,
          'name' => $request->name,
          'description' => $request->description,
          'date_activity' => createTimestamp($request->plan_date,$request->plan_time),
          'create_by' => auth()->id()
        ]);
      }
      DB::commit();
      return Response::json(null);
    }

    public function cari_lead($id)
    {
      return Response::json( Lead::find($id), 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_opportunity(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'customer_stage_id' => 'required'
      ]);
      DB::beginTransaction();
      $i=Lead::find($id);
      $code = new TransactionCode($i->company_id, 'opportunity');
      $code->setCode();
      $trx_code = $code->getCode();

      $con=Contact::create([
        'address' => $i->address,
        'city_id' => $i->city_id,
        'company_id' => $i->company_id,
        'contact_person' => $i->contact_person,
        'contact_person_email' => $i->contact_person_email,
        'contact_person_no' => $i->contact_person_phone,
        'email' => $i->email,
        'is_pelanggan' => 1,
        'limit_hutang' => 0,
        'limit_piutang' => 0,
        'name' => $i->name,
        'phone' => $i->phone,
        'phone2' => $i->phone2,
        'postal_code' => $i->postal_code,
      ]);

      $in=Inquery::create([
        'company_id' => $con->company_id,
        'lead_id' => $id,
        'customer_id' => $con->id,
        'sales_opportunity_id' => $request->sales_id,
        'customer_stage_id' => $request->customer_stage_id,
        'code_opportunity' => $trx_code,
        'create_by' => auth()->id(),
        'date_opportunity' => dateDB($request->date_opportunity),
        'description_opportunity' => $request->description_opportunity,
      ]);

      $i->update([
        'is_contact' => 1,
        'inquery_id' => $in->id,
        'step' => 2,
      ]);
      DB::commit();
    }
    public function store_inquery(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'customer_stage_id' => 'required'
      ]);
      DB::beginTransaction();
      $i=Lead::find($id);

      $code = new TransactionCode($i->company_id, 'inquery');
      $code->setCode();
      $trx_code = $code->getCode();

      $con=Contact::create([
        'address' => $i->address,
        'city_id' => $i->city_id,
        'company_id' => $i->company_id,
        'contact_person' => $i->contact_person,
        'contact_person_email' => $i->contact_person_email,
        'contact_person_no' => $i->contact_person_phone,
        'email' => $i->email,
        'is_pelanggan' => 1,
        'limit_hutang' => 0,
        'limit_piutang' => 0,
        'name' => $i->name,
        'phone' => $i->phone,
        'phone2' => $i->phone2,
        'postal_code' => $i->postal_code,
      ]);

      $in=Inquery::create([
        'company_id' => $con->company_id,
        'lead_id' => $id,
        'customer_id' => $con->id,
        'sales_inquery_id' => $request->sales_id,
        'customer_stage_id' => $request->customer_stage_id,
        'code_inquery' => $trx_code,
        'create_by' => auth()->id(),
        'status' => 2,
        'date_inquery' => dateDB($request->date_opportunity),
        'description_inquery' => $request->description_opportunity,
      ]);

      $i->update([
        'is_contact' => 1,
        'inquery_id' => $in->id,
        'step' => 3,
      ]);
      DB::commit();
    }
    public function store_quotation(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'customer_stage_id' => 'required',
        'bill_type' => 'required',
        'no_inquery' => 'required',
        'send_type' => 'required',
        'date_inquery' => 'required',
        'price_full_inquery' => 'integer'
      ]);
      DB::beginTransaction();
      $i=Lead::find($id);

      $code = new TransactionCode($i->company_id, 'quotation');
      $code->setCode();
      $trx_code = $code->getCode();

      $con=Contact::create([
        'address' => $i->address,
        'city_id' => $i->city_id,
        'company_id' => $i->company_id,
        'contact_person' => $i->contact_person,
        'contact_person_email' => $i->contact_person_email,
        'contact_person_no' => $i->contact_person_phone,
        'email' => $i->email,
        'is_pelanggan' => 1,
        'limit_hutang' => 0,
        'limit_piutang' => 0,
        'name' => $i->name,
        'phone' => $i->phone,
        'phone2' => $i->phone2,
        'postal_code' => $i->postal_code,
      ]);

      $q=Quotation::create([
        'company_id' => $con->company_id,
        'customer_id' => $con->id,
        'lead_id' => $id,
        'sales_id' => $request->sales_id,
        'customer_stage_id' => $request->customer_stage_id,
        'created_by' => auth()->id(),
        'code' => $trx_code,
        'no_inquery' => $request->no_inquery,
        'is_active' => 1,
        'bill_type' => $request->bill_type,
        'imposition' => $request->imposition,
        'piece_id' => $request->piece_id,
        'send_type' => $request->send_type,
        'price_full_inquery' => ($request->price_full_inquery?:0),
        'date_inquery' => dateDB($request->date_inquery),
        'description_inquery' => $request->description_inquery,
        'type_entry' => 1,
      ]);

      $i->update([
        'is_contact' => 1,
        'quotation_id' => $q->id,
        'step' => 4,
      ]);
      DB::commit();
    }

    public function delete_activity($id)
    {
      DB::beginTransaction();
      LeadActivity::find($id)->delete();
      DB::commit();
    }

    public function done_activity($id)
    {
      DB::beginTransaction();
      LeadActivity::find($id)->update([
        'is_done' => 1
      ]);
      DB::commit();
    }

    public function cancel_lead($id)
    {
      DB::beginTransaction();
      Lead::find($id)->update([
        'step' => 6,
        'cancel_by' => auth()->id(),
        'cancel_date' => Carbon::now()
      ]);
      DB::commit();
    }

    public function cancel_cancel_lead($id)
    {
      DB::beginTransaction();
      Lead::find($id)->update([
        'step' => 1,
      ]);
      DB::commit();
    }
}
