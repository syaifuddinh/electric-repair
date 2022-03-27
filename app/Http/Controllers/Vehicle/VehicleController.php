<?php

namespace App\Http\Controllers\Vehicle;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Vehicle;
use App\Model\VehicleJoint;
use App\Model\VehicleOwner;
use App\Model\Company;
use App\Model\Contact;
use App\Model\VehicleVariant;
use App\Model\VehicleContact;
use App\Model\VehicleChecklistItem;
use App\Model\VehicleChecklistDetailBody;
use App\Model\VehicleChecklistDetailItem;
use App\Model\VehicleInsurance;
use App\Model\VehicleType;
use App\Model\Account;
use App\Model\VehicleDocument;
use App\Model\TargetRate;
use bPDF;
use File;
use DB;
use Response;
use Carbon\Carbon;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dt = DB::table('vehicles')
        ->select('id', 'nopol', 'nopol AS name')
        ->get();
        $data['message'] = 'OK';
        $data['data'] = $dt;

        return response()->json($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['supplier']=Contact::whereRaw("is_supplier=1 or is_vendor=1")->where('vendor_status_approve', 2)->get();
      $data['vehicle_joint']=VehicleJoint::all();
      $data['vehicle_owner']=VehicleOwner::select('id', 'name')->get();
      $data['vehicle_variant']=VehicleVariant::select('id', 'name')->get();

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
        'company_id' => 'required',
        'code' => 'required|unique:vehicles,code',
        'nopol' => 'required|unique:vehicles,nopol',
        'vehicle_variant_id' => 'required',
        'machine_no' => 'required',
        'chassis_no' => 'required',
        'color' => 'required',
        'vehicle_owner_id' => 'required',
        'supplier_id' => 'required',
        'date_manufacture' => 'required',
        'is_active' => 'required',
        'is_internal' => 'required',
        'not_active_reason' => 'required_if:is_active,0',
        'stnk_no' => 'required',
        'stnk_name' => 'required',
        'stnk_address' => 'required',
        'stnk_date' => 'required',
        'bpkb_no' => 'required',
        'kir_date' => 'required',
        'initial_km' => 'required|integer',
        'last_km' => 'required|integer',
        'daily_distance' => 'required|integer',
        'is_trailer' => 'required',
        'trailer_size' => 'required_if:is_trailer,1',
        'max_tonase' => 'required_if:is_trailer,1',
        'max_volume' => 'required_if:is_trailer,1',
      ]);
      DB::beginTransaction();
      Vehicle::create([
        'company_id' => $request->company_id,
        'code' => $request->code,
        'nopol' => $request->nopol,
        'vehicle_variant_id' => $request->vehicle_variant_id,
        'chassis_no' => $request->chassis_no,
        'machine_no' => $request->machine_no,
        'color' => $request->color,
        'vehicle_owner_id' => $request->vehicle_owner_id,
        'supplier_id' => $request->supplier_id,
        'date_manufacture' => dateDB($request->date_manufacture),
        'date_operation' => dateDB($request->date_operation),
        'is_active' => $request->is_active,
        'not_active_reason' => $request->not_active_reason,
        'stnk_no' => $request->stnk_no,
        'stnk_date' => dateDB($request->stnk_date),
        'stnk_name' => $request->stnk_name,
        'stnk_address' => $request->stnk_address,
        'bpkb_no' => $request->bpkb_no,
        'kir_date' => dateDB($request->kir_date),
        'initial_km' => $request->initial_km,
        'initial_km_date' => dateDB($request->initial_km_date),
        'last_km' => $request->last_km,
        'last_km_date' => dateDB($request->last_km_date),
        'daily_distance' => $request->daily_distance,
        'gps_no' => $request->gps_no,
        'is_internal' => $request->is_internal,
        'serep_tire' => $request->serep_tire??0,
        'is_trailer' => $request->is_trailer,
        'trailer_size' => $request->trailer_size??0,
        'max_tonase' => $request->max_tonase??0,
        'max_volume' => $request->max_volume??0,
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
        $data['item']=Vehicle::with('company','vehicle_variant','supplier','vehicle_owner', 'vehicle_variant.vehicle_type:id,name')->where('id', $id)->first();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function card($id)
    {
      $data['item']=DB::table('vehicles')
      ->leftJoin('vehicle_variants','vehicle_variants.id','vehicles.vehicle_variant_id')
      ->leftJoin('contacts','contacts.id','vehicles.supplier_id')
      ->leftJoin('companies','companies.id','vehicles.company_id')
      ->where('vehicles.id', $id)
      ->selectRaw('
        vehicles.*,
        vehicle_variants.name as variant_name,
        contacts.name as supplier,
        companies.name as company
      ')->first();

      $data['job']=DB::table('delivery_order_drivers')
      ->where('vehicle_id', $id)
      ->selectRaw('ifnull(sum(if(is_finish=1,1,0)),0) as finished, ifnull(sum(if(is_finish=0,1,0)),0) as ongoing, count(id) as total')->first();

      $data['perawatan_terakhir']=DB::table('vehicle_maintenances')
      ->where('vehicle_id', $id)
      ->where('status', 5)
      ->select('vehicle_maintenances.id as id',DB::raw("MAX(date_realisasi) as tanggal"))
      ->first();

      $data['jenis_perawatan']=DB::table('vehicle_maintenance_details')
      ->join('vehicle_maintenance_types', 'vehicle_maintenance_details.vehicle_maintenance_type_id', '=', 'vehicle_maintenance_types.id')
      ->where('vehicle_maintenance_details.header_id', '=', $data['perawatan_terakhir']->id)
      ->select('name')
      ->first();
      // dd($data['perawatan_terakhir']);
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
      $data['supplier']=Contact::whereRaw("is_supplier=1 or is_vendor=1")->where('vendor_status_approve', 2)->get();
      $data['vehicle_joint']=VehicleJoint::all();
      $data['vehicle_owner']=VehicleOwner::all();
      $data['company']=companyAdmin(auth()->id());
      $data['vehicle_variant']=VehicleVariant::all();
      $data['item']=Vehicle::find($id);

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
      // dd($request);
      $request->validate([
        'company_id' => 'required',
        'code' => 'required|unique:vehicles,code,'.$id,
        'nopol' => 'required|unique:vehicles,nopol,'.$id,
        'vehicle_variant_id' => 'required',
        'machine_no' => 'required',
        'chassis_no' => 'required',
        'color' => 'required',
        'vehicle_owner_id' => 'required',
        'supplier_id' => 'required',
        'date_manufacture' => 'required',
        'is_active' => 'required',
        'is_internal' => 'required',
        'not_active_reason' => 'required_if:is_active,0',
        'stnk_no' => 'required',
        'stnk_name' => 'required',
        'stnk_address' => 'required',
        'stnk_date' => 'required',
        'bpkb_no' => 'required',
        'kir_date' => 'required',
        'initial_km' => 'required|integer',
        'last_km' => 'required|integer',
        'daily_distance' => 'required|integer',
        'is_trailer' => 'required',
        'trailer_size' => 'required_if:is_trailer,1',
        'max_tonase' => 'required_if:is_trailer,1',
        'max_volume' => 'required_if:is_trailer,1',
      ]);
      DB::beginTransaction();
      Vehicle::find($id)->update([
        'company_id' => $request->company_id,
        'code' => $request->code,
        'nopol' => $request->nopol,
        'vehicle_variant_id' => $request->vehicle_variant_id,
        'chassis_no' => $request->chassis_no,
        'machine_no' => $request->machine_no,
        'color' => $request->color,
        'vehicle_owner_id' => $request->vehicle_owner_id,
        'supplier_id' => $request->supplier_id,
        'date_manufacture' => dateDB($request->date_manufacture),
        'date_operation' => dateDB($request->date_operation),
        'is_active' => $request->is_active,
        'not_active_reason' => $request->not_active_reason,
        'stnk_no' => $request->stnk_no,
        'stnk_date' => dateDB($request->stnk_date),
        'stnk_name' => $request->stnk_name,
        'stnk_address' => $request->stnk_address,
        'bpkb_no' => $request->bpkb_no,
        'kir_date' => dateDB($request->kir_date),
        'initial_km' => $request->initial_km,
        'initial_km_date' => dateDB($request->initial_km_date),
        'last_km' => $request->last_km,
        'last_km_date' => dateDB($request->last_km_date),
        'daily_distance' => $request->daily_distance,
        'gps_no' => $request->gps_no,
        'is_internal' => $request->is_internal,
        'serep_tire' => $request->serep_tire??0,
        'is_trailer' => $request->is_trailer,
        'trailer_size' => $request->trailer_size??0,
        'max_tonase' => $request->max_tonase??0,
        'max_volume' => $request->max_volume??0,
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
      Vehicle::find($id)->delete();
      DB::commit();
      return Response::json(null);
    }

    public function driver($vid)
    {
      $data['driver']=Contact::where('is_driver', 1)->get();
      $data['detail']=VehicleContact::with('contact')->whereRaw("vehicle_id = $vid")->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_driver(Request $request,$vid)
    {
      $request->validate([
        'status' => 'required',
        'driver_id' => 'required',
      ]);
      DB::beginTransaction();
      $cek=VehicleContact::whereRaw("vehicle_id = $vid and contact_id = $request->driver_id")->count();
      if ($cek>0) {
        return Response::json(['message' => 'Driver Ini Sudah dimasukkan!'],500);
      }
      VehicleContact::create([
        'contact_id' => $request->driver_id,
        'driver_status' => $request->status,
        'vehicle_id' => $vid,
        'is_active' => 1,
      ]);
      DB::commit();
    }

    public function body($vid)
    {
      $data['item']=VehicleChecklistItem::whereRaw("vehicle_id = $vid")->orderBy('date_transaction','desc')->first();
      if (isset($data['item'])) {
        $data['detail_body']=VehicleChecklistDetailBody::with('vehicle_body')->where('header_id', $data['item']->id)->get();
        $data['detail_checklist']=VehicleChecklistDetailItem::with('vehicle_checklist')->where('header_id', $data['item']->id)->get();
      } else {
        $data['detail_body']=null;
        $data['detail_checklist']=null;
      }
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }


    function insurance($vid)
    {
      $data['insurance']=Contact::where('is_asuransi', 1)->get();
      $data['account']=Account::where('is_base', 0)->get();
      $data['detail']=VehicleInsurance::with('insurance')->where('vehicle_id', $vid)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_insurance(Request $request,$vid)
    {
      // dd($request);
      $request->validate([
        'start_date' => 'required',
        'end_date' => 'required',
        'payment' => 'required',
        'insurance_id' => 'required',
        'polis_no' => 'required',
        'type' => 'required',
        'premi' => 'required',
        'is_active' => 'required',
        'tjh' => 'required',
        'account_id' => 'required_if:payment,1',
        'termin' => 'required_if:payment,2',
      ]);
      DB::beginTransaction();
      $i=VehicleInsurance::create([
        'insurance_id' => $request->insurance_id,
        'account_id' => $request->account_id,
        'vehicle_id' => $vid,
        'type' => $request->type,
        'payment' => $request->payment,
        'termin' => ($request->termin?:0),
        'premi' => $request->premi,
        'polis_no' => $request->polis_no,
        'tjh' => $request->tjh,
        'start_date' => dateDB($request->start_date),
        'end_date' => dateDB($request->end_date),
        'date_credit' => Carbon::parse($request->start_date)->addDays(($request->termin?:0)),
        'is_active' => $request->is_active,
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function document($vid)
    {
      $data['detail']=VehicleDocument::where('vehicle_id', $vid)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_document(Request $request, $vid)
    {
      // dd($request);
      $request->validate([
        'file' => 'required',
        'type' => 'required'
      ]);
      $stt=[
        1 => 'STNK',
        2 => 'SIUP',
        3 => 'KEUR',
        4 => 'KIM-IMK',
        5 => 'PERBAIKAN',
        6 => 'BPKB',
        7 => 'FOTO',
        8 => 'LAIN',
      ];
      $file=$request->file('file');
      $filename="VEHICLE_".$vid."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();
      $file->move(public_path('files'), $filename);

      DB::beginTransaction();
      VehicleDocument::create([
        'vehicle_id' => $vid,
        'type' => str_replace("number:","",$request->type),
        'file_name' => 'files/'.$filename,
        'date_upload' => Carbon::now(),
        'extension' => $file->getClientOriginalExtension(),
        'description' => $request->description,
      ]);
      DB::commit();
      return Response::json(null);
    }

    public function rate($vid)
    {
      $data['years']=DB::select("select distinct year(period) as tahun from target_rates where vehicle_id = $vid");
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_rate(Request $request, $vid)
    {
      $request->validate([
        'year' => 'required'
      ]);
      $hit=DB::select("SELECT id as total from target_rates WHERE vehicle_id = $vid AND year(period) = '$request->year'");
      // dd(count($hit));
      if (count($hit)>0) {
        return Response::json(['message' => 'Tahun ini sudah ditambahkan!'],500);
      }
      DB::beginTransaction();
      for ($i=1; $i < 13; $i++) {
        TargetRate::create([
          'vehicle_id' => $vid,
          'create_by' => auth()->id(),
          'period' => Carbon::create($request->year,$i,1),
          'username' => auth()->user()->name
        ]);
      }
      DB::commit();

      return Response::json(null);
    }

    public function store_detail_rate(Request $request)
    {
      $request->validate([
        'id' => 'required',
        'plan' => 'required'
      ]);

      DB::beginTransaction();
      TargetRate::find($request->id)->update([
        'plan' => $request->plan
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function insurance_detail($id)
    {
        $data['item'] = VehicleInsurance::with('insurance')->find($id);
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function edit_insurance(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'start_date' => 'required',
        'end_date' => 'required',
        'payment' => 'required',
        'insurance_id' => 'required',
        'polis_no' => 'required',
        'type' => 'required',
        'premi' => 'required',
        'is_active' => 'required',
        'tjh' => 'required',
        'account_id' => 'required_if:payment,1',
        'termin' => 'required_if:payment,2',
      ]);
      DB::beginTransaction();
      $i=VehicleInsurance::find($id)->update([
        'insurance_id' => $request->insurance_id,
        'account_id' => $request->account_id,
        'type' => $request->type,
        'payment' => $request->payment,
        'termin' => ($request->termin?:0),
        'premi' => $request->premi,
        'polis_no' => $request->polis_no,
        'tjh' => $request->tjh,
        'start_date' => dateDB($request->start_date),
        'end_date' => dateDB($request->end_date),
        'date_credit' => Carbon::parse($request->start_date)->addDays(($request->termin?:0)),
        'is_active' => $request->is_active,
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_insurance($id)
    {
      DB::beginTransaction();

      VehicleInsurance::find($id)->delete();

      DB::commit();

      return Response::json(null);
    }

    public function delete_document(Request $request)
    {
      $item=DB::table('vehicle_documents')->where('id', $request->id)->first();
      File::delete(public_path().'/'.$item->file_name);
      DB::beginTransaction();
      VehicleDocument::find($request->id)->delete();
      DB::commit();

      return Response::json(null,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date        : 1 Maret 2020
      Description : Print PDF Laporan Rekap Pengecekan Kendaraan
      Developer   : Dimas
      Status      : Create
    */
    public function print($id,$date)
    {
      $date = date_create($date);
      $date = date_format($date, "Y-m-d");
      $date_part = explode('-', $date);

      $data['vehicle'] = DB::table('vehicles')->find($id);

      $data['drivers'] = DB::table('vehicle_contacts')
      ->join('contacts', 'vehicle_contacts.contact_id', '=', 'contacts.id')
      ->where('vehicle_contacts.vehicle_id', '=', $data['vehicle']->id)
      ->get();

      $data['area'] = DB::table('companies')->find($data['vehicle']->company_id);

      $data['vehicle_checklists'] = DB::table('vehicle_checklists')->get();
      $data['vehicle_bodies'] = DB::table('vehicle_bodies')->get();

      $data['vehicle_checklist_items'] = DB::table('vehicle_checklist_items')
      ->join('vehicle_checklist_detail_items', 'vehicle_checklist_items.id', '=', 'vehicle_checklist_detail_items.header_id')
      ->where('date_transaction', 'like', '%'.$date_part[0].'-'.$date_part[1].'%')
      ->where('vehicle_id', '=', $id)
      ->orderBy('date_transaction')
      ->get();

      $data['vehicle_checklist_bodies'] = DB::table('vehicle_checklist_items')
      ->join('vehicle_checklist_detail_bodies', 'vehicle_checklist_items.id', '=', 'vehicle_checklist_detail_bodies.header_id')
      ->where('date_transaction', 'like', '%'.$date_part[0].'-'.$date_part[1].'%')
      ->where('vehicle_id', '=', $id)
      ->orderBy('date_transaction')
      ->get();

      $data['vehicle_variant'] = DB::table('vehicle_variants')
      ->where('id', '=', $data['vehicle']->vehicle_variant_id)
      ->first();

      $data['bbm_type'] = DB::table('bbm_types')->find($data['vehicle_variant']->bbm_type_id);
      $data['remark'] = DB::table('print_remarks')->first();
      if(count($data['vehicle_checklist_items']) > 0 )
      {
        return bPDF::loadView('pdf.rekap_pengecekan_kendaraan', $data)
                   ->setPaper("F4")
                   ->stream();
      }

      else
      {
        return view('layouts.data-pengecekan-not-found');
      }
    }

}
