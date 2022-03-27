<?php

namespace App\Http\Controllers\Vendor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Contact;
use App\Model\VendorType;
use App\Model\Bank;
use App\Model\Account;
use App\Model\Company;
use App\Model\City;
use App\Model\AddressType;
use App\Model\ContactAddress;
use App\Model\ContactFile;
use App\Model\VendorPrice;
use App\Model\Route as Trayek;
use App\Model\VehicleType;
use App\Model\Moda;
use App\Model\Commodity;
use App\Model\Piece;
use App\Model\Service;
use App\Model\PriceList;
use App\Model\ServiceType;
use App\Model\ContainerType;
use App\Model\Rack;

use Response;
use DB;
use Carbon\Carbon;
use File;

class VendorRegisterController extends Controller
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
        //
        $data['bank'] = Bank::all();
        $data['vendor_type'] = VendorType::all();
        $data['account'] = Account::where('is_base', 0)->get();
        $data['company'] = companyAdmin(auth()->id());
        $data['city'] = City::all();

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
        'name' => 'required',
        'address' => 'required',
        'city_id' => 'required',
        'email' => 'required|email',
        'vendor_type_id' => 'required_if:is_vendor,1',
      ]);

      DB::beginTransaction();
      Contact::create([
        'address' => $request->address,
        'akun_hutang' => $request->akun_hutang,
        'akun_piutang' => $request->akun_piutang,
        'akun_um_customer' => $request->akun_um_customer,
        'akun_um_supplier' => $request->akun_um_supplier,
        'city_id' => $request->city_id,
        'company_id' => $request->company_id,
        'contact_person' => $request->contact_person,
        'contact_person_email' => $request->contact_person_email,
        'contact_person_no' => $request->contact_person_no,
        'description' => $request->description,
        'email' => $request->email,
        'fax' => $request->fax,
        'is_vendor' => $request->is_vendor,
        'limit_hutang' => $request->limit_hutang,
        'limit_piutang' => $request->limit_piutang,
        'name' => $request->name,
        'npwp' => $request->npwp,
        'phone' => $request->phone,
        'phone2' => $request->phone2,
        'pkp' => $request->pkp,
        'postal_code' => $request->postal_code,
        'rek_bank_id' => $request->rek_bank_id,
        'rek_cabang' => $request->rek_cabang,
        'rek_milik' => $request->rek_milik,
        'rek_no' => $request->rek_no,
        'term_of_payment' => $request->term_of_payment,
        'vendor_type_id' => $request->vendor_type_id,
        'vendor_register_date' => Carbon::now(),
        'vendor_status_approve' => 1,
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
      $c=Contact::with('company','hutang','piutang','bank','um_supplier','um_customer','vendor_type')
        ->where('id', $id)->first();
      return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
      $data['bank'] = Bank::all();
      $data['vendor_type'] = VendorType::all();
      $data['account'] = Account::where('is_base', 0)->get();
      $data['company'] = companyAdmin(auth()->id());
      $data['city'] = City::all();
      $data['item'] = Contact::find($id);

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
        'company_id' => 'required',
        'name' => 'required',
        'address' => 'required',
        'city_id' => 'required',
        'email' => 'required|email',
        'vendor_type_id' => 'required_if:is_vendor,1',
      ]);

      DB::beginTransaction();
      Contact::find($id)->update([
        'address' => $request->address,
        'akun_hutang' => $request->akun_hutang,
        'akun_piutang' => $request->akun_piutang,
        'akun_um_customer' => $request->akun_um_customer,
        'akun_um_supplier' => $request->akun_um_supplier,
        'city_id' => $request->city_id,
        'company_id' => $request->company_id,
        'contact_person' => $request->contact_person,
        'contact_person_email' => $request->contact_person_email,
        'contact_person_no' => $request->contact_person_no,
        'description' => $request->description,
        'email' => $request->email,
        'fax' => $request->fax,
        'is_vendor' => $request->is_vendor,
        'limit_hutang' => $request->limit_hutang,
        'limit_piutang' => $request->limit_piutang,
        'name' => $request->name,
        'npwp' => $request->npwp,
        'phone' => $request->phone,
        'phone2' => $request->phone2,
        'pkp' => $request->pkp,
        'postal_code' => $request->postal_code,
        'rek_bank_id' => $request->rek_bank_id,
        'rek_cabang' => $request->rek_cabang,
        'rek_milik' => $request->rek_milik,
        'rek_no' => $request->rek_no,
        'term_of_payment' => $request->term_of_payment,
        'vendor_type_id' => $request->vendor_type_id,
        'vendor_register_date' => Carbon::now(),
        'vendor_status_approve' => 1,
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

    public function document($id)
    {
      $c=ContactFile::where('contact_id', $id)->orderBy('created_at')->get();
      return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }

    public function upload_file(Request $request, $id)
    {
      $request->validate([
        'file' => 'required',
        'name' => 'required|unique:contact_files,name'
      ]);
      $file=$request->file('file');
      $filename="VENDOR_".$id."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();
      $file->move(public_path('files'), $filename);
      DB::beginTransaction();
      ContactFile::create([
        'contact_id' => $id,
        'name' => $request->name,
        'file_name' => 'files/'.$filename,
        'date_upload' => date('Y-m-d'),
        'extension' => $file->getClientOriginalExtension()
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function approve($id)
    {
      DB::beginTransaction();
      Contact::find($id)->update([
        'vendor_approve_date' => Carbon::now(),
        'vendor_user_approve' => auth()->id(),
        'vendor_status_approve' => 2
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function create_price($id)
    {
      $data['company']=companyAdmin(auth()->id());
      $data['commodity']=Commodity::all();
      $data['service']=Service::with('service_type')->get();
      $data['piece']=Piece::all();
      $data['moda']=Moda::all();
      $data['commodity']=Commodity::all();
      $data['vehicle_type']=VehicleType::all();
      $data['container_type']=ContainerType::all();
      $data['route']=Trayek::all();
      $data['rack']=Rack::all();
      $data['item']=Contact::find($id);
      // $data['service_type']=ServiceType::orderBy('id')->get();
      $data['type_1']=[];
      $data['type_2']=[];
      $data['type_3']=[];
      $data['type_4']=[];
      $data['type_5']=[];
      $data['type_6']=[];
      $data['type_7']=[];
      foreach ($data['service'] as $value) {
        if ($value->service_type->id==1) {
          $data['type_1'][]=$value->id;
        } elseif ($value->service_type->id==2) {
          $data['type_2'][]=$value->id;
        } elseif ($value->service_type->id==3) {
          $data['type_3'][]=$value->id;
        } elseif ($value->service_type->id==4) {
          $data['type_4'][]=$value->id;
        } elseif ($value->service_type->id==5) {
          $data['type_5'][]=$value->id;
        } elseif ($value->service_type->id==6) {
          $data['type_6'][]=$value->id;
        } else {
          $data['type_7'][]=$value->id;
        }
      }

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_price(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        // 'stype_id' => 'required',
        'service_id' => 'required',
        'company_id' => 'required',
        'route_id' => 'required_if:stype_id,1|required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4',
        'commodity_id' => 'required_if:stype_id,5',
        'name' => 'required',
        'piece_id' => 'required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7',
        'moda_id' => 'required_if:stype_id,1',
        'vehicle_type_id' => 'required_if:stype_id,1|required_if:stype_id,3|required_if:stype_id,4',
        "min_tonase" => 'required_if:stype_id,1|integer',
        "price_tonase" => 'required_if:stype_id,1|required_if:stype_id,5|integer',
        "min_volume" => 'required_if:stype_id,1|required_if:stype_id,5|integer',
        "price_volume" => 'required_if:stype_id,1|integer',
        "min_item" => 'required_if:stype_id,1|integer',
        "price_item" => 'required_if:stype_id,1|integer',
        "price_full" => 'required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7|integer',
        'price_handling_tonase' => 'integer|required_if:stype_id,5',
        'price_handling_volume' => 'integer|required_if:stype_id,5',
        'container_type_id' => 'required_if:stype_id,2',
        'rack_id' => 'required_if:stype_id,5',
      ]);

      DB::beginTransaction();
      $piece=Piece::find($request->piece_id);
      VendorPrice::create([
        "vendor_id" => $id,
        "company_id" => $request->company_id,
        "route_id" => $request->route_id,
        'commodity_id' => ($request->commodity_id?:1),
        "name" => $request->name,
        "piece_id" => $request->piece_id,
        "service_id" => $request->service_id,
        "moda_id" => $request->moda_id,
        "vehicle_type_id" => $request->vehicle_type_id,
        "description" => $request->description,
        "min_tonase" => $request->min_tonase,
        "price_tonase" => $request->price_tonase,
        "min_volume" => $request->min_volume,
        "price_volume" => $request->price_volume,
        "min_item" => $request->min_item,
        "price_item" => $request->price_item,
        "price_full" => $request->price_full,
        "piece_name" => ($piece?$piece->name:null),
        "created_by" => auth()->id(),
        'price_handling_tonase' => $request->price_handling_tonase,
        'price_handling_volume' => $request->price_handling_volume,
        'rack_id' => $request->rack_id,
        'container_type_id' => $request->container_type_id,
        'service_type_id' => $request->stype_id,
      ]);
      DB::commit();

      return Response::json(null);

    }

    public function edit_price($id)
    {
      $data['company']=companyAdmin(auth()->id());
      $data['commodity']=Commodity::all();
      $data['service']=Service::with('service_type')->get();
      $data['piece']=Piece::all();
      $data['moda']=Moda::all();
      $data['commodity']=Commodity::all();
      $data['vehicle_type']=VehicleType::all();
      $data['container_type']=ContainerType::all();
      $data['route']=Trayek::all();
      $data['rack']=Rack::all();
      $data['item']=VendorPrice::find($id);
      // $data['service_type']=ServiceType::orderBy('id')->get();
      $data['type_1']=[];
      $data['type_2']=[];
      $data['type_3']=[];
      $data['type_4']=[];
      $data['type_5']=[];
      $data['type_6']=[];
      $data['type_7']=[];
      foreach ($data['service'] as $value) {
        if ($value->service_type->id==1) {
          $data['type_1'][]=$value->id;
        } elseif ($value->service_type->id==2) {
          $data['type_2'][]=$value->id;
        } elseif ($value->service_type->id==3) {
          $data['type_3'][]=$value->id;
        } elseif ($value->service_type->id==4) {
          $data['type_4'][]=$value->id;
        } elseif ($value->service_type->id==5) {
          $data['type_5'][]=$value->id;
        } elseif ($value->service_type->id==6) {
          $data['type_6'][]=$value->id;
        } else {
          $data['type_7'][]=$value->id;
        }
      }

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function update_price(Request $request, $id)
    {
      $request->validate([
        'service_id' => 'required',
        'company_id' => 'required',
        'route_id' => 'required_if:stype_id,1|required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4',
        'commodity_id' => 'required_if:stype_id,5',
        'name' => 'required',
        'piece_id' => 'required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7',
        'moda_id' => 'required_if:stype_id,1',
        'vehicle_type_id' => 'required_if:stype_id,1|required_if:stype_id,3|required_if:stype_id,4',
        "min_tonase" => 'required_if:stype_id,1',
        "price_tonase" => 'required_if:stype_id,1|required_if:stype_id,5',
        "min_volume" => 'required_if:stype_id,1|required_if:stype_id,5',
        "price_volume" => 'required_if:stype_id,1',
        "min_item" => 'required_if:stype_id,1',
        "price_item" => 'required_if:stype_id,1',
        "price_full" => 'required_if:stype_id,2|required_if:stype_id,3|required_if:stype_id,4|required_if:stype_id,6|required_if:stype_id,7',
        'price_handling_tonase' => 'required_if:stype_id,5',
        'price_handling_volume' => 'required_if:stype_id,5',
        'container_type_id' => 'required_if:stype_id,2',
        'rack_id' => 'required_if:stype_id,5',
      ]);

      DB::beginTransaction();
      $piece=Piece::find($request->piece_id);
      VendorPrice::find($id)->update([
        "company_id" => $request->company_id,
        "route_id" => $request->route_id,
        'commodity_id' => ($request->commodity_id?:1),
        "name" => $request->name,
        "piece_id" => $request->piece_id,
        "service_id" => $request->service_id,
        "moda_id" => $request->moda_id,
        "vehicle_type_id" => $request->vehicle_type_id,
        "description" => $request->description,
        "min_tonase" => $request->min_tonase,
        "price_tonase" => $request->price_tonase,
        "min_volume" => $request->min_volume,
        "price_volume" => $request->price_volume,
        "min_item" => $request->min_item,
        "price_item" => $request->price_item,
        "price_full" => $request->price_full,
        "piece_name" => ($piece?$piece->name:null),
        "created_by" => auth()->id(),
        'price_handling_tonase' => $request->price_handling_tonase,
        'price_handling_volume' => $request->price_handling_volume,
        'rack_id' => $request->rack_id,
        'container_type_id' => $request->container_type_id,
        'service_type_id' => $request->stype_id,
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_file($id)
    {
      DB::beginTransaction();
      $fl=ContactFile::find($id);
      // Storage::delete($fl->filename);
      $s=File::delete(public_path().'/'.$fl->file_name);
      // dd($s);
      if ($s) {
        $fl->delete();
      }
      DB::commit();

      return Response::json(null);
    }
}
