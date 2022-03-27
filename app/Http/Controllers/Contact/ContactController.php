<?php

namespace App\Http\Controllers\Contact;

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
use App\Model\ContactDocument;
use App\User;
use Response;
use File;
use ImageOptimizer;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
      $cust = DB::table('contacts');
      $cust = $cust->where('is_pelanggan', 1);
      $cust = $cust->where('is_active', 1);
      $cust = $cust->selectRaw('id,name')->get();
      return response()->json($cust,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 20-03-2020
      Description : Menampilkan semua penerima
      Developer : Didin
      Status : Create
    */
    public function penerima()
    {
        $penerima = DB::table('contacts')
        ->whereIsPenerima(1)
        ->select('id', 'name', 'address')
        ->get();

        return Response::json($penerima,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 07-04-2020
      Description : Menampilkan semua customer yg sudah diapprove
      Developer : Didin
      Status : Create
    */
    public function customer()
    {
        $customer = DB::table('contacts')
        ->whereIsPelanggan(1)
        ->whereIsActive(1)
        ->select('id', 'name', 'address', 'company_id')
        ->get();

        return Response::json($customer,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 07-04-2020
      Description : Menampilkan semua driver
      Developer : Didin
      Status : Create
    */
    public function driver()
    {
        $customer = DB::table('contacts')
        ->whereIsDriver(1)
        ->whereIsActive(1)
        ->select('id', 'name')
        ->get();

        return Response::json($customer,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 07-04-2020
      Description : Menampilkan semua sales
      Developer : Didin
      Status : Create
    */
    public function sales()
    {
        $customer = DB::table('contacts')
        ->whereIsSales(1)
        ->whereIsActive(1)
        ->select('id', 'name')
        ->get();

        return Response::json($customer,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 07-04-2020
      Description : Menampilkan semua supplier
      Developer : Didin
      Status : Create
    */
    public function supplier()
    {
        $customer = DB::table('contacts')
        ->whereIsSupplier(1)
        ->whereIsActive(1)
        ->select('id', 'name')
        ->get();

        return Response::json($customer,200,[],JSON_NUMERIC_CHECK);
    }

    /*
      Date : 17-04-2020
      Description : Menampilkan daftar vendor
      Developer : Didin
      Status : Create
    */
    public function vendor()
    {
        $vendor = DB::table('contacts')
        ->whereIsVendor(1)
        ->whereVendorStatusApprove(2)
        ->select('id', 'name')
        ->get();

        return Response::json($vendor,200,[],JSON_NUMERIC_CHECK);
    }

    public function receivable_value($id)
    {
      $dt = DB::table('receivables as r');
      $dt = $dt->where('r.contact_id', $id);
      $dt = $dt->sum(DB::raw('(r.debet-r.credit)'));
      return response()->json(['value' => $dt]);
    }

    public function payable_value($id)
    {
      $dt = DB::table('payables as p');
      $dt = $dt->where('p.contact_id', $id);
      $dt = $dt->sum(DB::raw('(p.debet-p.credit)'));
      return response()->json(['value' => $dt]);
    }

    public function receivable_count($id)
    {
      $dt = DB::table('receivables as r');
      $dt = $dt->where('r.contact_id', $id);
      $dt = $dt->whereRaw('(r.debet-r.credit) < ?', [0]);
      $dt = $dt->count();
      return response()->json(['value' => $dt]);
    }
    public function payable_count($id)
    {
      $dt = DB::table('payables as p');
      $dt = $dt->where('p.contact_id', $id);
      $dt = $dt->whereRaw('(p.credit-p.debet) < ?', [0]);
      $dt = $dt->count();
      return response()->json(['value' => $dt]);
    }

    /*
      Date : 07-04-2020
      Description : Menampilkan semua pegawai
      Developer : Didin
      Status : Create
    */
    public function pegawai()
    {
        $pegawai = DB::table('contacts')
        ->whereIsPegawai(1)
        ->select('id', 'name')
        ->get();

        return Response::json($pegawai,200,[],JSON_NUMERIC_CHECK);
    }


    /*
      Date : 07-04-2020
      Description : Menyetujui customer
      Developer : Didin
      Status : Create
    */
    public function approveCustomer($id)
    {
        $customer = DB::table('contacts')
        ->whereId($id)
        ->update([
          'is_approved_customer' => 1
        ]);


        return Response::json(['message' => 'Customer berhasil di-approve'], 200,[],JSON_NUMERIC_CHECK);
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
      $data['city'] = City::all();
      $data['supplier']=Contact::whereRaw("1 = 1")->select('id','name','address','company_id')->get();

      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
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
      $data['contact'] = DB::table('contacts')->selectRaw('id,name')->get();
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
      // dd($request->all());
      foreach($request->all() as $key => $val){
        if($val === "null" || $val === "NaN"){
          $request->merge([
            $key => null
          ]);
        }
      }

      $req = [
        'name' => 'required',
        'address' => 'required',
        'city_id' => 'required',
        'email' => 'required_if:is_vendor,0|unique:contacts,email|email',
        'vendor_type_id' => 'required_if:is_vendor,1',
        'driver_status' => 'required_if:is_driver,1',
        'parent_id' => 'required_if:driver_status,4',
        'address_type_id' => 'required_if:is_pengirim,1|required_if:is_penerima,1',
        'no_ktp' => 'nullable|numeric',
        'file_ktp' => 'nullable|file|max:2048',
        'file_siup' => 'file|max:5120',
        'file_npwp' => 'file|max:5120',
        'file_tdp' => 'file|max:5120',
        'file_sppkp' => 'file|max:5120',
      ];

      $msg = [
        'name.required' => 'Nama tidak boleh kosong',
        'address.required' => 'Alamat tidak boleh kosong',
        'city_id.required' => 'Kota tidak boleh kosong',
        'email.required_if' => 'Email tidak boleh kosong',
        'email.email' => 'Email tidak valid',
        'email.unique' => 'Email telah digunakan',
        'driver_status.required_if' => 'Status driver tidak boleh kosong',
        'vendor_type_id.required_if' => 'Tipe vendor tidak boleh kosong',
        'parent_id.required_if' => 'Vendor tidak boleh kosong',
        'address_type_id.required_if' => 'Tipe alamat tidak boleh kosong',
        'no_ktp.required' => 'Nomor KTP tidak boleh kosong',
        'no_ktp.numeric' => 'Nomor KTP harus berupa angka',
        // 'file_ktp.required_if' => 'File KTP wajib diupload',
        'file_ktp.max' => 'File KTP maksimal :max  KB',
        'file_npwp.max' => 'File NPWP maksimal :max  KB',
        'file_siup.max' => 'File SIUP maksimal :max  KB',
        'file_sppkp.max' => 'File SPPKP maksimal :max  KB',
        'file_tdp.max' => 'File TDP maksimal :max  KB',
      ];

      if($request->is_pelanggan == 1){
        $req['owner_name'] = 'nullable';
        $req['position'] = 'required';
        $req['category'] = 'required';
        $req['no_ktp'] = 'nullable|numeric|digits:16';
        $req['email'] = 'nullable|unique:contacts,email|email';

        $msg['owner_name.required'] = 'Nama tidak boleh kosong';
        $msg['name.required'] = 'Nama Perusahaan tidak boleh kosong';
        $msg['position.required'] = 'Jabatan tidak boleh kosong';
        $msg['category.required'] = 'Kategori tidak boleh kosong';
        // $msg['no_ktp.required'] = 'Nomor KTP tidak boleh kosong';
        $msg['no_ktp.digits'] = 'Nomor KTP harus 16 karakter';
        $msg['email.unique'] = 'Email telah digunakan';
        $msg['email.email'] = 'Email tidak valid';
      }

      $request->validate($req, $msg);

      if(($request->pkp ?? null) == 1) {
          if(!$request->filled('no_ktp') && !$request->filled('npwp')) {
              return Response::json(['message' => 'Salah satu dari No. KTP atau NPWP harus diisi'], 421);
          }

          if($request->filled('no_ktp')) {
              if(!preg_match('/^([\d\s]{16})$/', $request->no_ktp)) {
                  return Response::json(['message' => 'No. KTP harus 16 karakter'], 421);
              }
          }

          if($request->filled('npwp_induk')) {
              $request->npwp_induk = str_replace('_', '', $request->npwp_induk);
              if(!preg_match('/^([\d\D]{2}\.[\d\D]{3}\.[\d\D]{3}\.[\d\D]-[\d\D]{3}\.[\d\D]{3})$/', $request->npwp_induk)) {
                  return Response::json(['message' => 'NPWP Induk tidak valid'], 421);
              }
          }
      }

      if($request->filled('npwp')) {
          $request->npwp = str_replace('_', '', $request->npwp);
          if(!preg_match('/^([\d\D]{2}\.[\d\D]{3}\.[\d\D]{3}\.[\d\D]-[\d\D]{3}\.[\d\D]{3})$/', $request->npwp)) {
              return Response::json(['message' => 'NPWP tidak valid'], 421);
          }
      }
      if($request->filled('contact_person_npwp')) {
        $request->contact_person_npwp = str_replace('_', '', $request->contact_person_npwp);
        if(!preg_match('/^([\d\D]{2}\.[\d\D]{3}\.[\d\D]{3}\.[\d\D]-[\d\D]{3}\.[\d\D]{3})$/', $request->contact_person_npwp)) {
            return response()->json(['message' => 'NPWP Contact Person tidak valid'], 421);
        }
      }

      $inputContact = [
        'address' => $request->address,
        'latitude' => $request->latitude ?? 0,
        'longitude' => $request->longitude ?? 0,
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
        'parent_id' => $request->parent_id,
        'limit_hutang' => $request->limit_hutang??0,
        'limit_piutang' => $request->limit_piutang??0,
        'customer_service_id' => $request->customer_service_id,
        'sales_id' => $request->sales_id,
        'driver_status' => $request->driver_status,
        'name' => $request->name,
        'npwp' => $request->npwp_induk,
        'npwp_cabang' => $request->npwp,
        'no_ktp' => (string)$request->no_ktp,
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

        'category' => $request->category,
        'position' => $request->position,
        'contact_person_no_2' => $request->contact_person_no_2,
        'contact_person_position' => $request->contact_person_position,
        'contact_person_npwp' => $request->contact_person_npwp,
        'no_tdp' => $request->no_tdp,
        'no_siup' => $request->no_siup,
        'no_sppkp' => $request->no_sppkp,
        'website' => $request->website,
        'purchase_purpose' => $request->purchase_purpose,
        'personal_facebook_account' => $request->personal_facebook_account,
        'personal_instagram_account' => $request->personal_instagram_account,
        'company_facebook_account' => $request->company_facebook_account,
        'company_instagram_account' => $request->company_instagram_account,
        'company_customer_service' => $request->company_customer_service,
        'owner_name' => $request->owner_name,
      ];

      DB::beginTransaction();
      $c=Contact::create($inputContact);
      if (isset($request->id)) {
        ContactAddress::create([
          'contact_id' => $request->id,
          'contact_address_id' => $c->id,
          'contact_bill_id' => $request->tertagih_id ?? $c->id,
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

      if($request->has('file_ktp')){
        if($request->file('file_ktp')){
          $filename="KTP_".$c->id."_".date('Ymd_His').'.'.$request->file('file_ktp')->getClientOriginalExtension();
          $fname="KTP_".$c->id."_".date('Ymd_His');

          $request->file('file_ktp')->move(public_path('files'), $filename);

          ContactDocument::create([
              'contact_id' => $c->id,
              'name' => $fname,
              'file_name' => 'files/'.$filename,
              'description' => 'File KTP',
              'file_extension' => $request->file('file_ktp')->getClientOriginalExtension()
          ]);
        }
      }
      if($request->has('file_npwp')){
        if($request->file('file_npwp')){
          $filename="NPWP_".$c->id."_".date('Ymd_His').'.'.$request->file('file_npwp')->getClientOriginalExtension();
          $fname="NPWP_".$c->id."_".date('Ymd_His');

          $request->file('file_npwp')->move(public_path('files'), $filename);

          ContactDocument::create([
              'contact_id' => $c->id,
              'name' => $fname,
              'file_name' => 'files/'.$filename,
              'description' => 'File NPWP',
              'file_extension' => $request->file('file_npwp')->getClientOriginalExtension()
          ]);
        }
      }
      if($request->has('file_tdp')){
        if($request->file('file_tdp')){
          $filename="TDP_".$c->id."_".date('Ymd_His').'.'.$request->file('file_tdp')->getClientOriginalExtension();
          $fname="TDP_".$c->id."_".date('Ymd_His');

          $request->file('file_tdp')->move(public_path('files'), $filename);

          ContactDocument::create([
              'contact_id' => $c->id,
              'name' => $fname,
              'file_name' => 'files/'.$filename,
              'description' => 'File TDP',
              'file_extension' => $request->file('file_tdp')->getClientOriginalExtension()
          ]);
        }
      }
      if($request->has('file_siup')){
        if($request->file('file_siup')){
          $filename="SIUP_".$c->id."_".date('Ymd_His').'.'.$request->file('file_siup')->getClientOriginalExtension();
          $fname="SIUP_".$c->id."_".date('Ymd_His');

          $request->file('file_siup')->move(public_path('files'), $filename);

          ContactDocument::create([
              'contact_id' => $c->id,
              'name' => $fname,
              'file_name' => 'files/'.$filename,
              'description' => 'File SIUP',
              'file_extension' => $request->file('file_siup')->getClientOriginalExtension()
          ]);
        }
      }
      if($request->has('file_sppkp')){
        if($request->file('file_sppkp')){
          $filename="SPPKP_".$c->id."_".date('Ymd_His').'.'.$request->file('file_sppkp')->getClientOriginalExtension();
          $fname="SPPKP_".$c->id."_".date('Ymd_His');

          $request->file('file_sppkp')->move(public_path('files'), $filename);

          ContactDocument::create([
              'contact_id' => $c->id,
              'name' => $fname,
              'file_name' => 'files/'.$filename,
              'description' => 'File SPPKP',
              'file_extension' => $request->file('file_sppkp')->getClientOriginalExtension()
          ]);
        }
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

    /*
      Date : 08-04-2020
      Description : Menampilkan detail kontrak
      Developer : Didin
      Status : Edit
    */
    public function show($id)
    {
      $c=Contact::with('company:id,name','hutang','piutang','bank','um_supplier','um_customer')
      ->leftJoin('contacts AS CS', 'CS.id', 'contacts.customer_service_id')
      ->leftJoin('contacts AS S', 'S.id', 'contacts.sales_id')
      ->where('contacts.id', $id)
      ->select('contacts.*', 'S.name AS sales_name', 'CS.name AS customer_service_name')
      ->first();
      return Response::json($c, 200);
    }
    /*
      Date : 08-04-2020
      Description : Menampilkan sales dan customer service dari
                    kontak terkait
      Developer : Didin
      Status : Create
    */
    public function showPic($id)
    {
      $contact = DB::table('contacts AS C')
      ->leftJoin('contacts AS S', 'S.id', 'C.sales_id')
      ->leftJoin('contacts AS CS', 'CS.id', 'C.customer_service_id')
      ->where('C.id', $id)
      ->select('S.name AS sales_name', 'CS.name AS customer_service_name')
      ->first();

      if($contact == null) {
          return Response::json(['message' => 'Kontak tidak ditemukan'], 404);
      }

      return Response::json($contact, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 16-07-2020
      Description : Menampikan satu field dari kontak tertentu
      Developer : Didin
      Status : Create
    */
    public function showField($id, $column)
    {
      $contact = DB::table('contacts')
      ->whereId($id)
      ->select($column)
      ->first();

      if($contact == null) {
          return Response::json(['message' => 'Kontak tidak ditemukan'], 404);
      }

      return Response::json($contact, 200, [], JSON_NUMERIC_CHECK);
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
      $data['item']->no_ktp = (string)$data['item']->no_ktp;

      return Response::json($data, 200);
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
      foreach($request->all() as $key => $val){
        if($val === "null" || $val === "NaN"){
          $request->merge([
            $key => null
          ]);
        }
      }

      $req = [
        'no_ktp' => 'nullable|numeric',
        'name' => 'required',
        'address' => 'required',
        'city_id' => 'required',
        'email' => 'required|unique:contacts,email,'.$id,
        'vendor_type_id' => 'required_if:is_vendor,1',
        'driver_status' => 'required_if:is_driver,1',
        'parent_id' => 'required_if:driver_status,4',
        'address_type_id' => 'required_if:is_pengirim,1|required_if:is_penerima,1'
      ];

      $msg = [
        'name.required' => 'Nama tidak boleh kosong',
        'vendor_type_id.required_if' => 'Tipe Vendor harus diisi jika kontak bertipe vendor',
        'address_type_id.required_if' => 'Tipe Alamat harus diisi jika kontak bertipe alamat pengirim/penerima',
        'driver_status.required_if' => 'Status driver tidak boleh kosong',
        'parent_id.required_if' => 'Vendor tidak boleh kosong',
        'no_ktp.size' => 'Panjang nomor KTP harus 16 huruf',
        'no_ktp.required' => 'Nomor KTP tidak boleh kosong',
        'no_ktp.numeric' => 'Nomor KTP harus berupa angka',
        'position.required' => 'Jabatan tidak boleh kosong',
      ];

      if($request->is_pelanggan == 1){
        $req['owner_name'] = 'nullable';
        $req['position'] = 'required';
        $req['category'] = 'required';
        $req['no_ktp'] = 'required|numeric|digits:16';

        $msg['owner_name.required'] = 'Nama tidak boleh kosong';
        $msg['name.required'] = 'Nama Perusahaan tidak boleh kosong';
        $msg['position.required'] = 'Jabatan tidak boleh kosong';
        $msg['category.required'] = 'Kategori tidak boleh kosong';
        $msg['no_ktp.required'] = 'Nomor KTP tidak boleh kosong';
        $msg['no_ktp.digits'] = 'Nomor KTP harus 16 karakter';
      }

      $request->validate($req, $msg);

      if(($request->pkp ?? null) == 1) {
          if(!$request->filled('no_ktp') && !$request->filled('npwp')) {
              return Response::json(['message' => 'Salah satu dari No. KTP atau NPWP harus diisi'], 421);
          }
          if($request->filled('no_ktp')) {
              if(!preg_match('/^([\d\s]{16})$/', $request->no_ktp)) {
                  return Response::json(['message' => 'No. KTP tidak valid'], 421);
              }
          }

          if($request->filled('npwp_induk')) {
              $request->npwp_induk = str_replace('_', '', $request->npwp_induk);
              if(!preg_match('/^([\d\D]{2}\.[\d\D]{3}\.[\d\D]{3}\.[\d\D]-[\d\D]{3}\.[\d\D]{3})$/', $request->npwp_induk)) {
                  return Response::json(['message' => 'NPWP Induk tidak valid'], 421);
              }
          }

          if($request->filled('npwp')) {
              $request->npwp = str_replace('_', '', $request->npwp);
              if(!preg_match('/^([\d\D]{2}\.[\d\D]{3}\.[\d\D]{3}\.[\d\D]-[\d\D]{3}\.[\d\D]{3})$/', $request->npwp)) {
                  return Response::json(['message' => 'NPWP tidak valid'], 421);
              }
          }

          if($request->filled('contact_person_npwp')) {
              $request->contact_person_npwp = str_replace('_', '', $request->contact_person_npwp);
              if(!preg_match('/^([\d\D]{2}\.[\d\D]{3}\.[\d\D]{3}\.[\d\D]-[\d\D]{3}\.[\d\D]{3})$/', $request->contact_person_npwp)) {
                  return response()->json(['message' => 'NPWP Contact Person tidak valid'], 421);
              }
          }
      }

      $updateContact = [
        'address' => $request->address,
        'latitude' => $request->latitude  ?? 0,
        'longitude' => $request->longitude  ?? 0,
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
        'sales_id' => $request->sales_id ?? null,
        'customer_service_id' => $request->customer_service_id ?? null,
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
        'parent_id' => $request->parent_id,
        'limit_hutang' => $request->limit_hutang??0,
        'limit_piutang' => $request->limit_piutang??0,
        'customer_service_id' => $request->customer_service_id,
        'sales_id' => $request->sales_id,
        'driver_status' => $request->driver_status,
        'name' => $request->name,
        'no_ktp' => (string)$request->no_ktp,
        'npwp' => $request->npwp_induk,
        'npwp_cabang' => $request->npwp,
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

        'category' => $request->category,
        'position' => $request->position,
        'contact_person_no_2' => $request->contact_person_no_2,
        'contact_person_position' => $request->contact_person_position,
        'contact_person_npwp' => $request->contact_person_npwp,
        'no_tdp' => $request->no_tdp,
        'no_siup' => $request->no_siup,
        'no_sppkp' => $request->no_sppkp,
        'website' => $request->website,
        'purchase_purpose' => $request->purchase_purpose,
        'personal_facebook_account' => $request->personal_facebook_account,
        'personal_instagram_account' => $request->personal_instagram_account,
        'company_facebook_account' => $request->company_facebook_account,
        'company_instagram_account' => $request->company_instagram_account,
        'company_customer_service' => $request->company_customer_service,
        'owner_name' => $request->owner_name,
      ];

      DB::beginTransaction();
      Contact::find($id)->update($updateContact);
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

    /*
      Date : 27-07-2020
      Description : Mengaktifkan kontak
      Developer : Didin
      Status : Create
    */
    public function activate($id)
    {
      DB::beginTransaction();
      $existing = DB::table('contacts')
      ->whereId($id)
      ->count();

      if($existing == 0) {
          return Response::json(['message' => 'Data tidak ditemukan'], 404);
      } else {
          DB::table('contacts')
          ->whereId($id)
          ->update([
              'is_active' => 1
          ]);
      }
      DB::commit();

      return Response::json(['message' => 'Data berhasil diaktifkan']);
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
        if($request->passwords && $request->confirm_password) {
            if($request->passwords != $request->confirm_password) {
                throw new Exception('Password must be same with password confirmation');
            }
        }
        if ($request->user_id) {
            $request->validate([
                'emails' => 'required|unique:users,email,'.$request->user_id,
                'passwords' => 'required',
                'confirm_password' => 'required',
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
                'is_driver' => $request->is_driver
            ]);
        } else {

            $request->validate([
                'emails' => 'required|unique:users,email',
                'passwords' => 'required',
                'confirm_password' => 'required',
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
      // $item=ContactFile::find($id);
      $item=ContactDocument::find($id);
      File::delete(public_path().'/'.$item->file_name);
      $item->delete();
      DB::commit();

      return Response::json(null);
    }
}
