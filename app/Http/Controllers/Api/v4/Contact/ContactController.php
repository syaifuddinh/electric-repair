<?php

namespace App\Http\Controllers\Api\v4\Contact;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Contact;
use App\Model\VendorType;
use App\Model\Bank;
use App\Model\Account;
use App\Model\Asset;
use App\Model\Company;
use App\Model\City;
use App\Model\AddressType;
use App\Model\ContactAddress;
use App\Model\ContactFile;
use App\User;
use Response;
use DB;
use File;
use ImageOptimizer;
use Carbon\Carbon;
use PhpParser\Node\Expr\Empty_;

class ContactController extends Controller
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
      $data['asset_group']=DB::table('asset_groups')->get();
      $data['asset']=Asset::with('asset_group')->where('status', 2)->get();
      $data['bank'] = Bank::all();
      $data['vendor_type'] = VendorType::all();
      $data['address_type'] = AddressType::all();
      $data['account'] = Account::where('is_base', 0)->get();
      $data['company'] = companyAdmin(auth()->id());
      $data['city'] = City::all();
      $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address','company_id')->get();
      $data['supplier']=Contact::whereRaw("1 = 1")->select('id','name','address','company_id')->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 16-04-2020
      Description : Menampilkan daftar customer
      Developer : Didin
      Status : Edit
  */
    public function listCustomer(Request $request)
    {
      # code...
      DB::enableQueryLog();
      $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address','company_id');
      if(isset($request->keyword)) {
        $data['customer'] = $data['customer']->whereRaw("(`name` LIKE '%$request->keyword%' OR `address` LIKE '%$request->keyword%')");
      }
      if(isset($request->company_id)) {
        $data['customer'] = $data['customer']->where("company_id", $request->company_id);
      }

      $data['customer'] = $data['customer']->get();

      return $data;
    }

    public function create_address()
    {
      $data['bank'] = Bank::all();
      $data['vendor_type'] = VendorType::all();
      $data['account'] = Account::where('is_base', 0)->get();
      $data['company'] = companyAdmin(auth()->id());
      $data['city'] = City::all();
      $data['address_type'] = AddressType::all();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function create_address_f()
    {
      $data['contact'] = Contact::all();
      $data['address_type'] = AddressType::all();

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
        'email' => 'required|unique:contacts,email',
        'vendor_type_id' => 'required_if:is_vendor,1',
        'address_type_id' => 'required_if:is_pengirim,1|required_if:is_penerima,1'
      ]);

      DB::beginTransaction();
      $c=Contact::create([
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
        'is_asuransi' => $request->is_asuransi,
        'is_depo_bongkar' => $request->is_depo_bongkar,
        'is_driver' => $request->is_driver,
        'is_helper' => $request->is_helper,
        'is_investor' => $request->is_investor,
        'is_kurir' => $request->is_kurir,
        'is_pegawai' => $request->is_pegawai,
        'is_pelanggan' => $request->is_pelanggan,
        'is_penerima' => $request->is_penerima,
        'is_pengirim' => $request->is_pengirim,
        'is_sales' => $request->is_sales,
        'is_supplier' => $request->is_supplier,
        'is_vendor' => $request->is_vendor,
        'is_staff_gudang' => $request->is_staff_gudang,
        'limit_hutang' => $request->limit_hutang,
        'limit_piutang' => $request->limit_piutang,
        'name' => $request->name,
        'npwp' => $request->npwp,
        'pegawai_no' => $request->pegawai_no,
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
        'address_type_id' => $request->address_type_id,
      ]);
      if (isset($request->id)) {
        ContactAddress::create([
          'contact_id' => $c->id,
          'contact_address_id' => $c->id,
          'contact_bill_id' => $c->id,
          'address_type_id' => $request->address_type_id
        ]);
        $ca=ContactAddress::where('contact_id', $request->id)->where('contact_address_id','!=',$request->id)->get();
        foreach ($ca as $key => $value) {
          ContactAddress::create([
            'contact_id' => $c->id,
            'contact_address_id' => $value->contact_address_id,
            'contact_bill_id' => $value->contact_bill_id,
            'address_type_id' => $value->address_type_id
          ]);
        }
      } else {
        ContactAddress::create([
          'contact_id' => $c->id,
          'contact_address_id' => $c->id,
          'contact_bill_id' => $c->id,
          'address_type_id' => $request->address_type_id
        ]);
      }

      if (isset($request->job_order_customer_id)) {
        ContactAddress::create([
          'contact_id' => $c->id,
          'contact_address_id' => $c->id,
          'contact_bill_id' => $c->id,
          'address_type_id' => $request->address_type_id
        ]);
        ContactAddress::create([
          'contact_id' => $request->job_order_customer_id,
          'contact_address_id' => $c->id,
          'contact_bill_id' => $request->tertagih_id,
          'address_type_id' => $request->address_type_id
        ]);
      }
      DB::commit();

      return Response::json(null);
    }

    public function store_address_f(Request $request)
    {
      // dd($request);
      $request->validate([
        'contact_id' => 'required',
      ]);
      DB::beginTransaction();
      ContactAddress::create([
        'contact_id' => $request->contact_id,
        'contact_address_id' => $request->contact_address_id,
        'address_type_id' => $request->address_type_id,
        'contact_bill_id' => $request->contact_bill_id,
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function store_address(Request $request)
    {
      $request->validate([
        'name' => 'required',
        'address' => 'required',
        'city_id' => 'required',
        'email.*' => 'required|email',
      ]);

      DB::beginTransaction();
      $c=Contact::create([
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
        'is_asuransi' => $request->is_asuransi,
        'is_depo_bongkar' => $request->is_depo_bongkar,
        'is_driver' => $request->is_driver,
        'is_helper' => $request->is_helper,
        'is_investor' => $request->is_investor,
        'is_kurir' => $request->is_kurir,
        'is_pegawai' => $request->is_pegawai,
        'is_pelanggan' => $request->is_pelanggan,
        'is_penerima' => $request->is_penerima,
        'is_pengirim' => $request->is_pengirim,
        'is_sales' => $request->is_sales,
        'is_supplier' => $request->is_supplier,
        'is_vendor' => $request->is_vendor,
        'limit_hutang' => $request->limit_hutang,
        'limit_piutang' => $request->limit_piutang,
        'name' => $request->name,
        'npwp' => $request->npwp,
        'pegawai_no' => $request->pegawai_no,
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
      ]);

      ContactAddress::create([
        'contact_id' => $request->contact_id,
        'contact_address_id' => $c->id,
        'address_type_id' => $request->address_type_id,
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
      $c=Contact::with('company','hutang','piutang','bank','um_supplier','um_customer')->where('id', $id)->first();
      return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }
    public function show_address($id)
    {
      $addr=ContactAddress::find($id);
      $c=Contact::with('company','hutang','piutang','bank','um_supplier','um_customer')->where('id', $addr->contact_address_id)->first();
      return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }

    public function edit_address($id)
    {
      $data['address']=ContactAddress::find($id);
      $data['item']=Contact::find($data['address']->contact_address_id);
      $data['address_type'] = AddressType::all();
      $data['contact'] = Contact::all();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function update_address(Request $request, $id)
    {
      $request->validate([
        'name' => 'required'
      ]);
      DB::beginTransaction();
      $addr=ContactAddress::find($id);
      Contact::find($addr->contact_address_id)->update([
        'name' => $request->name
      ]);
      $addr->update([
        'contact_bill_id' => $request->contact_bill_id,
        'address_type_id' => $request->address_type_id,
      ]);
      DB::commit();
      return Response::json(null);
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
      $data['address_type'] = AddressType::all();
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
      // dd($request);
      $request->validate([
        'name' => 'required',
        'address' => 'required',
        'city_id' => 'required',
        'email' => 'required|unique:contacts,email,'.$id,
        'vendor_type_id' => 'required_if:is_vendor,1',
        'address_type_id' => 'required_if:is_pengirim,1|required_if:is_penerima,1'
      ],[
        'vendor_type_id.required_if' => 'Tipe Vendor harus diisi jika kontak bertipe vendor',
        'address_type_id.required_if' => 'Tipe Alamat harus diisi jika kontak bertipe alamat pengirim/penerima',
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
        'is_asuransi' => $request->is_asuransi,
        'is_depo_bongkar' => $request->is_depo_bongkar,
        'is_driver' => $request->is_driver,
        'is_helper' => $request->is_helper,
        'is_investor' => $request->is_investor,
        'is_kurir' => $request->is_kurir,
        'is_pegawai' => $request->is_pegawai,
        'is_pelanggan' => $request->is_pelanggan,
        'is_penerima' => $request->is_penerima,
        'is_pengirim' => $request->is_pengirim,
        'is_sales' => $request->is_sales,
        'is_supplier' => $request->is_supplier,
        'is_vendor' => $request->is_vendor,
        'limit_hutang' => $request->limit_hutang,
        'limit_piutang' => $request->limit_piutang,
        'name' => $request->name,
        'npwp' => $request->npwp,
        'pegawai_no' => $request->pegawai_no,
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
        'address_type_id' => $request->address_type_id,
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
      Contact::find($id)->update([ 'is_active' => 0]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_address($id)
    {
      DB::beginTransaction();
      ContactAddress::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function save_as($id)
    {
      DB::beginTransaction();
      $c=Contact::find($id);
      $c_new=Contact::create([
        'company_id' => $c->company_id,
        'code' => $c->code,
        'name' => $c->name,
        'address' => $c->address,
        'city_id' => $c->city_id,
        'postal_code' => $c->postal_code,
        'phone' => $c->phone,
        'phone2' => $c->phone2,
        'fax' => $c->fax,
        'email' => $c->email,
      ]);
      DB::commit();
    }

    public function user_application($id)
    {
      $data['item']=DB::table('contacts')->where('id', $id)->first();
      $data['user']=User::where('contact_id', $id)->first();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_user_application(Request $request, $id)
    {
      DB::beginTransaction();
      if ($request->user_id) {
        $request->validate([
          'emails' => 'required|unique:users,email,'.$request->user_id,
          'passwords' => 'required',
          'is_customer' => 'required',
          'is_vendor' => 'required',
          'is_driver' => 'required',
        ]);
        User::find($request->user_id)->update([
          'email' => $request->emails,
          'pass_text' => $request->passwords,
          'password' => bcrypt($request->passwords),
          'is_customer' => $request->is_customer,
          'is_admin' => 1,
          'is_vendor' => $request->is_vendor,
          'is_driver' => $request->is_driver,
        ]);
      } else {
        // dd(explode('@',$request->emails)[0]);
        $request->validate([
          'emails' => 'required',
          'passwords' => 'required',
          'is_customer' => 'required',
          'is_vendor' => 'required',
          'is_driver' => 'required',
        ]);
        $c=Contact::find($id);
        User::create([
          'email' => $request->emails,
          'name' => $c->name,
          'api_token' => str_random(100),
          'company_id' => $c->company_id,
          'contact_id' => $id,
          'username' => explode('@',$request->emails)[0],
          'pass_text' => $request->passwords,
          'group_id' => 1,
          'is_admin' => 1,
          'password' => bcrypt($request->passwords),
          'is_customer' => $request->is_customer,
          'is_vendor' => $request->is_vendor,
          'is_driver' => $request->is_driver,
        ]);
      }
      DB::commit();
      return Response::json(['message' => 'User Aplikasi Berhasil disimpan!']);
    }

    public function contact_store_user(Request $request,$id)
    {
      $request->validate([
        'password' => 'required'
      ],[
        'password.required' => 'Password harus diisi'
      ]);
      DB::beginTransaction();
      Contact::find($id)->update([
        'password' => bcrypt($request->password),
        'api_token' => "c_".str_random(98)
      ]);
      DB::commit();
    }

    public function upload_document(Request $request, $id)
    {
      // dd($request->file('file'));
      $request->validate([
        'name' => 'required',
        'file' => 'required|mimetypes:image/jpeg,image/png,application/pdf',
      ],[
        'file.mimetypes' => 'File Harus Berupa Gambar atau PDF!',
        'file.required' => 'File belum ada!',
        'name.required' => 'Nama File Harus Diisi!'
      ]);
      DB::beginTransaction();
      $item=Contact::find($id);
      $file=$request->file('file');
      $file_name="CONTACT_".$id."_".date('Ymd_His').'_'.str_random(6).'.'.$file->getClientOriginalExtension();
      ContactDocument::create([
        'contact_id' => $id,
        'name' => $request->name,
        'file_name' => 'files/'.$file_name,
        'file_extension' => $file->getClientOriginalExtension(),
        'description' => $request->description
      ]);
      $file->move(public_path('files'),$file_name);
      DB::commit();

      return Response::json(null);
    }

    public function show_file($id)
    {
      $data['item']=DB::table('contact_documents')->where('contact_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function delete_file($id)
    {
      DB::beginTransaction();
      $item=ContactFile::find($id);
      File::delete(public_path().'/'.$item->file_name);
      $item->delete();
      DB::commit();

      return Response::json(null);
    }

    public function show_contact($id){
      $contact = DB::table('contacts')
      ->where('id', $id)
      ->select('id', 'name', 'address', 'email', 'phone', 'phone2')
      ->first();

      if(Empty($contact)){
        return Response::json(['status' => 'ERROR', 'message' => 'Data tidak ditemukan!', 'data' => null], 422, [], JSON_NUMERIC_CHECK);
      }
      else{
        return Response::json(['status' => 'OK', 'message' => 'Data ditemukan!', 'data' => $contact], 200, [], JSON_NUMERIC_CHECK);
      }
    }
}
