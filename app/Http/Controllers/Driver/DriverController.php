<?php

namespace App\Http\Controllers\Driver;

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
use App\Model\VehicleContact;
use Response;
use DB;
use File;
use ImageOptimizer;
use Carbon\Carbon;

class DriverController extends Controller
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
      $data['bank'] = Bank::all();
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
      // dd($request);
      $request->validate([
        'name' => 'required|unique:contacts,name',
        'address' => 'required',
        'city_id' => 'required',
        'email' => 'required|email',
        'driver_status' => 'required',
        'vehicle_id' => 'required',
        'password' => 'required'
      ]);

      DB::beginTransaction();
      $c=Contact::create([
        'address' => $request->address,
        'city_id' => $request->city_id,
        'company_id' => $request->company_id,
        'description' => $request->description,
        'email' => $request->email,
        'is_driver' => 1,
        'name' => $request->name,
        'npwp' => $request->npwp,
        'pegawai_no' => $request->pegawai_no,
        'phone' => $request->phone,
        'phone2' => $request->phone2,
        'postal_code' => $request->postal_code,
        'rek_bank_id' => $request->rek_bank_id,
        'rek_cabang' => $request->rek_cabang,
        'rek_milik' => $request->rek_milik,
        'rek_no' => $request->rek_no,
        'driver_status' => $request->driver_status,
        'password' => bcrypt($request->password),
        'api_token' => str_random(100)
      ]);

      VehicleContact::create([
        'contact_id' => $c->id,
        'vehicle_id' => $request->vehicle_id,
        'is_active' => 1
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
      $data['item']=Contact::with('company')->where('id', $id)->first();
      $data['vehicle']=DB::table('vehicles')->select('id','code','nopol','company_id')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function history($id)
    {
      $data['item']=DB::table('manifests')
      ->leftJoin('vehicles','vehicles.id','=','manifests.vehicle_id')
      ->leftJoin('routes','routes.id','=','manifests.route_id')
      ->where('manifests.driver_id', $id)
      ->select([
        'manifests.code',
        'manifests.date_manifest',
        'vehicles.nopol',
        'routes.name as route',
        'manifests.depart',
        'manifests.arrive',
      ])->get();
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
      $data['bank'] = Bank::all();
      $data['company'] = companyAdmin(auth()->id());
      $data['city'] = City::all();
      $data['item'] = Contact::find($id);
      $data['vehicle']=DB::table('vehicles')->select('id','code','nopol','company_id')->get();

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
        'name' => 'required|unique:contacts,name,'.$id,
        'address' => 'required',
        'city_id' => 'required',
        'email' => 'required|email',
        'driver_status' => 'required'
      ]);

      DB::beginTransaction();
      $c=Contact::find($id)->update([
        'address' => $request->address,
        'city_id' => $request->city_id,
        'company_id' => $request->company_id,
        'description' => $request->description,
        'email' => $request->email,
        'is_driver' => 1,
        'name' => $request->name,
        'npwp' => $request->npwp,
        'pegawai_no' => $request->pegawai_no,
        'phone' => $request->phone,
        'phone2' => $request->phone2,
        'postal_code' => $request->postal_code,
        'rek_bank_id' => $request->rek_bank_id,
        'rek_cabang' => $request->rek_cabang,
        'rek_milik' => $request->rek_milik,
        'rek_no' => $request->rek_no,
        'driver_status' => $request->driver_status,
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
      Contact::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function vehicle_list($id)
    {
      $data['item']=VehicleContact::with('vehicle','vehicle.vehicle_variant')->where('contact_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_vehicle(Request $request, $id)
    {
      $request->validate([
        'driver_status' => 'required',
        'vehicle_id' => 'required',
      ]);
      $vc=VehicleContact::where('contact_id', $id)->get();
      $inarr=[];
      foreach ($vc as $key => $value) {
        $inarr[]=$value->vehicle_id;
      }
      if (in_array($request->vehicle_id,$inarr)) {
        return Response::json(['message' => 'Kendaraan ini sudah dimasukkan!'],500);
      }
      DB::beginTransaction();
      VehicleContact::create([
        'contact_id' => $id,
        'vehicle_id' => $request->vehicle_id,
        'driver_status' => $request->driver_status,
        'is_active' => 1
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_vehicle($id)
    {
      DB::beginTransaction();
      VehicleContact::find($id)->delete();
      DB::commit();
      return Response::json(null);
    }

    public function upload_file(Request $request, $id)
    {
      $request->validate([
        'file' => 'mimetypes:image/jpeg,image/png'
      ]);
      DB::beginTransaction();
      $item=Contact::find($id);
      if ($item->file_name) {
        File::delete(public_path().'/'.$item->file_name);
      }
      $file=$request->file('file');
      $filename="PROFILE_".$id."_".date('Ymd_His').'_'.str_random(6).'.'.$file->getClientOriginalExtension();
      $item->update([
        'file_name' => 'files/'.$filename,
        'file_extension' => $file->getClientOriginalExtension()
      ]);
      $file->move(public_path('files'), $filename);

      ImageOptimizer::optimize(public_path('files').'/'.$filename);
      DB::commit();

      return Response::json(null);
    }

    public function store_application(Request $request, $id)
    {
      $request->validate([
        'email' => 'required|unique:contacts,email,'.$id,
        'password' => 'required|min:6',
        'password_confirmation' => 'required|same:password'
      ],[
        'email.required' => 'Email wajib diisi',
        'email.unique' => 'Email ini sudah digunakan',
        'password.unique' => 'Password wajib diisi',
        'password.required' => 'Password wajib diisi',
        'password.min' => 'Panjang password minimal 6 karakter',
        'password_confirmation.required' => 'Harap memasukkan kembali password baru anda',
        'password_confirmation.same' => 'Harap ulangi kembali password anda',
      ]);

      DB::beginTransaction();
      Contact::find($id)->update([
        'email' => $request->email,
        'password' => bcrypt($request->password),
        'api_token' => str_random(100)
      ]);
      DB::commit();
    }
}
