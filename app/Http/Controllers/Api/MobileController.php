<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Model\Company;
use App\Model\Warehouse;
use App\Model\City;
use App\Model\Rack;
use App\Model\Contact;
use App\Model\Category;
use App\Model\Item;
use App\Model\WarehouseReceipt;
use Response;
use DB;
use Auth;

class MobileController extends Controller
{
  public function login(Request $request)
  {
    $request->validate([
      'username' => 'required',
      'password' => 'required',
    ]);
    $auth=Auth::attempt([
      'email' => $request->username,
      'password' => $request->password,
    ]);
    if ($auth) {
      User::where('email', $request->username)->update([
        'api_token' => str_random(100)
      ]);
      $user=User::where('email', $request->username)->first();
      return Response::json(['status' => 'OK', 'message' => 'Anda berhasil login!','data' => $user],200);
    } else {
      return Response::json(['status' => 'ERROR', 'message' => 'Username atau Password tidak cocok!', 'data' => null],500);
    }
  }

  public function company_list(Request $request)
  {
    $data=Company::all();
    return Response::json(['status' => 'OK', 'message' => 'List Cabang','data' => $data],200);
  }

  public function city_list(Request $request)
  {
    $data=City::leftJoin('provinces','provinces.id','=','cities.province_id')->select('cities.name','cities.id','cities.type','cities.province_id','provinces.name as province_name')->get();
    return Response::json(['status' => 'OK', 'message' => 'List Kota','data' => $data],200);
  }

  public function warehouse_list(Request $request)
  {
    $data=Warehouse::all();
    return Response::json(['status' => 'OK', 'message' => 'List Gudang','data' => $data],200);
  }

  public function detail_warehouse(Request $request)
  {
    try {
      $data=Warehouse::leftJoin('companies','companies.id','=','warehouses.company_id')
      ->leftJoin('cities','cities.id','=','warehouses.city_id')
      ->where('warehouses.id',$request->id)
      ->select('warehouses.id','warehouses.company_id','companies.name as cabang','cities.name as kota', 'warehouses.code','warehouses.name', 'warehouses.address','warehouses.capacity_volume','warehouses.capacity_tonase')
      ->first();
      return Response::json(['status' => 'OK', 'message' => 'Detail Gudang','data' => $data],200);
    } catch (\Exception $e) {
      return Response::json(['status' => 'ERROR', 'message' => $e->getMessage(), 'data' => null],500);
    }
  }

  public function tambah_warehouse(Request $request)
  {
    $request->validate([
      'company_id' => 'required',
      'city_id' => 'required',
      'code' => 'required|unique:warehouses,code',
      'warehouse_type_id' => 'required',
      'name' => 'required',
    ]);
    try {
      DB::beginTransaction();
      Warehouse::create([
        'company_id' => $request->company_id,
        'city_id' => $request->city_id,
        'warehouse_type_id' => $request->warehouse_type_id, //1 iternal 2 eksternal
        'code' => $request->code,
        'name' => $request->name,
        'address' => $request->address,
        'capacity' => 0,
        'capacity_volume' => $request->capacity_volume??0,
        'capacity_tonase' => $request->capacity_tonase??0,
      ]);
      DB::commit();
      return Response::json(['status' => 'OK', 'message' => 'Data Berhasil Disimpan!','data' => null],200);
    } catch (\Exception $e) {
      return Response::json(['status' => 'ERROR', 'message' => $e->getMessage(), 'data' => null],500);
    }
  }

  public function delete_warehouse(Request $request)
  {
    try {
      DB::beginTransaction();
      Warehouse::find($request->id)->delete();
      DB::commit();
      return Response::json(['status' => 'OK', 'message' => 'Data Berhasil Dihapus!','data' => null],200);
    } catch (\Exception $e) {
      return Response::json(['status' => 'ERROR', 'message' => 'Error Hapus Gudang, sudah terdapat transaksi pada gudang ini!', 'data' => null],500);
    }
  }

  public function update_warehouse(Request $request)
  {
    $request->validate([
      'id' => 'required',
      'company_id' => 'required',
      'city_id' => 'required',
      'code' => 'required|unique:warehouses,code',
      'warehouse_type_id' => 'required',
      'name' => 'required',
    ]);
    try {
      DB::beginTransaction();
      Warehouse::find($request->id)->update([
        'company_id' => $request->company_id,
        'city_id' => $request->city_id,
        'warehouse_type_id' => $request->warehouse_type_id, //1 iternal 2 eksternal
        'code' => $request->code,
        'name' => $request->name,
        'address' => $request->address,
        'capacity' => 0,
        'capacity_volume' => $request->capacity_volume??0,
        'capacity_tonase' => $request->capacity_tonase??0,
      ]);
      DB::commit();
      return Response::json(['status' => 'OK', 'message' => 'Data Berhasil Disimpan!','data' => null],200);
    } catch (\Exception $e) {
      return Response::json(['status' => 'ERROR', 'message' => $e->getMessage(), 'data' => null],500);
    }
  }

  public function bin_list(Request $request)
  {
    $data=Rack::leftJoin('warehouses','warehouses.id','=','racks.warehouse_id')->select('racks.id as id_bin_location','warehouses.name','warehouses.code as warehouse_code','racks.code as rack_code','racks.capacity_volume','racks.capacity_tonase')->get();
    return Response::json(['status' => 'OK', 'message' => 'List Rack','data' => $data],200);
  }

  public function detail_bin(Request $request)
  {
    try {
      $data=Rack::leftJoin('warehouses','warehouses.id','=','racks.warehouse_id')
      ->where('racks.id',$request->id)
      ->select('racks.id as id_bin_location','warehouses.name','warehouses.code as warehouse_code','racks.code as rack_code','racks.capacity_volume','racks.capacity_tonase')
      ->first();
      return Response::json(['status' => 'OK', 'message' => 'Detail Bin Location','data' => $data],200);
    } catch (\Exception $e) {
      return Response::json(['status' => 'ERROR', 'message' => $e->getMessage(), 'data' => null],500);
    }
  } 


  public function create_bin(Request $request)
  {
    $request->validate([
      'warehouse_id' => 'required',
      'code' => 'required',  
    ]);
    try {
      DB::beginTransaction();
      Rack::create([
        'warehouse_id' => $request->warehouse_id,
        'code' => $request->code,
        'description' => $request->description,
        'capacity_volume' => $request->capacity_volume??0,
        'capacity_tonase' => $request->capacity_tonase??0,
      ]);
      DB::commit();
      return Response::json(['status' => 'OK', 'message' => 'Data Berhasil Disimpan!','data' => null],200);
    } catch (\Exception $e) {
      return Response::json(['status' => 'ERROR', 'message' => $e->getMessage(), 'data' => null],500);
    }
  }

  public function update_bin(Request $request)
  {
    $request->validate([
      'id' => 'required',
      'warehouse_id' => 'required',
      'code' => 'required',  
    ]);
    try {
      DB::beginTransaction();
      Rack::find($request->id)->update([
        'warehouse_id' => $request->warehouse_id, //1 iternal 2 eksternal
        'code' => $request->code,
        'description' => $request->description,
        'capacity_volume' => $request->capacity_volume??0,
        'capacity_tonase' => $request->capacity_tonase??0,
      ]);
      DB::commit();
      return Response::json(['status' => 'OK', 'message' => 'Data Berhasil Diubah!','data' => null],200);
    } catch (\Exception $e) {
      return Response::json(['status' => 'ERROR', 'message' => $e->getMessage(), 'data' => null],500);
    }
  }

  public function delete_bin(Request $request)
  {
    try {
      DB::beginTransaction();
      Rack::find($request->id)->delete();
      DB::commit();
      return Response::json(['status' => 'OK', 'message' => 'Data Berhasil Dihapus!','data' => null],200);
    } catch (\Exception $e) {
      return Response::json(['status' => 'ERROR', 'message' => 'Error Hapus Gudang, sudah terdapat transaksi pada gudang ini!', 'data' => null],500);
    }
  }

  public function employee_list(Request $request)
  {
    $data=Contact::leftJoin('companies','companies.id','=','contacts.company_id')->leftJoin('cities','cities.id','=','contacts.city_id')->leftJoin('banks', 'banks.id', '=', 'contacts.rek_bank_id')->where('contacts.is_pegawai',1)->select('contacts.id','companies.name as cabang','contacts.name as nama_pegawai','contacts.address','cities.name as kota','contacts.phone','contacts.phone2','contacts.fax','contacts.email','contacts.contact_person','contacts.contact_person_email','contacts.contact_person_no', 'contacts.pegawai_no','contacts.npwp','contacts.rek_no','contacts.rek_milik', 'banks.code', 'banks.name', 'contacts.rek_cabang')->get();
    return Response::json(['status' => 'OK', 'message' => 'List Pegawai','data' => $data],200);
  }

  public function contact_list(Request $request)
  {
    $data=Contact::leftJoin('companies','companies.id','=','contacts.company_id')
    ->leftJoin('cities','cities.id','=','contacts.city_id')
    ->leftJoin('banks', 'banks.id', '=', 'contacts.rek_bank_id')
    ->leftJoin('vendor_types', 'vendor_types.id', '=', 'contacts.vendor_type_id')
    ->leftJoin('address_types', 'address_types.id', '=', 'contacts.address_type_id')
    ->select('contacts.id','companies.name as cabang','contacts.name as nama_pegawai','contacts.address','cities.name as kota','contacts.phone','contacts.phone2','contacts.fax','contacts.email','contacts.contact_person','contacts.contact_person_email','contacts.contact_person_no', 'contacts.pegawai_no','contacts.npwp','contacts.rek_no','contacts.rek_milik', 'banks.code', 'banks.name', 'contacts.rek_cabang', 'vendor_types.name as tipe_vendor', 'contacts.is_pegawai', 'contacts.is_investor','contacts.is_pelanggan', 'contacts.is_asuransi', 'contacts.is_supplier','contacts.is_depo_bongkar', 'contacts.is_helper', 'contacts.is_driver', 'contacts.is_vendor', 'contacts.is_sales', 'contacts.is_kurir', 'contacts.is_pengirim', 'contacts.is_penerima', 'address_types.name as tipe_alamat')
    ->get();
    return Response::json(['status' => 'OK', 'message' => 'List Kontak','data' => $data],200);
  }

  public function vendor_list(Request $request)
  {
    $data=Contact::leftJoin('companies','companies.id','=','contacts.company_id')
    ->leftJoin('cities','cities.id','=','contacts.city_id')
    ->leftJoin('banks', 'banks.id', '=', 'contacts.rek_bank_id')
    ->leftJoin('vendor_types', 'vendor_types.id', '=', 'contacts.vendor_type_id')
    ->leftJoin('address_types', 'address_types.id', '=', 'contacts.address_type_id')
    ->where('contacts.is_vendor',1)
    ->select('contacts.id','companies.name as cabang','contacts.name as nama_pegawai','contacts.address','cities.name as kota','contacts.phone','contacts.phone2','contacts.fax','contacts.email','contacts.contact_person','contacts.contact_person_email','contacts.contact_person_no', 'contacts.pegawai_no','contacts.npwp','contacts.rek_no','contacts.rek_milik', 'banks.code', 'banks.name', 'contacts.rek_cabang', 'vendor_types.name as tipe_vendor', 'address_types.name as tipe_alamat')
    ->get();
    return Response::json(['status' => 'OK', 'message' => 'List Vendor','data' => $data],200);
  }

  public function supplier_list(Request $request)
  {
    $data=Contact::leftJoin('companies','companies.id','=','contacts.company_id')
    ->leftJoin('cities','cities.id','=','contacts.city_id')
    ->leftJoin('banks', 'banks.id', '=', 'contacts.rek_bank_id')
    ->leftJoin('vendor_types', 'vendor_types.id', '=', 'contacts.vendor_type_id')
    ->leftJoin('address_types', 'address_types.id', '=', 'contacts.address_type_id')
    ->where('contacts.is_supplier',1)
    ->select('contacts.id','companies.name as cabang','contacts.name as nama_pegawai','contacts.address','cities.name as kota','contacts.phone','contacts.phone2','contacts.fax','contacts.email','contacts.contact_person','contacts.contact_person_email','contacts.contact_person_no', 'contacts.pegawai_no','contacts.npwp','contacts.rek_no','contacts.rek_milik', 'banks.code', 'banks.name', 'contacts.rek_cabang', 'vendor_types.name as tipe_vendor', 'address_types.name as tipe_alamat')
    ->get();
    return Response::json(['status' => 'OK', 'message' => 'List Supplier','data' => $data],200);
  }

  public function category_list(Request $request)
  {
    $data=Category::where('parent_id','!=',null)
    ->select('id','code','name','is_tire','is_asset','is_jasa','is_ban_luar','is_ban_dalam','is_marset','description')
    ->get();
    return Response::json(['status' => 'OK', 'message' => 'List Kategori','data' => $data],200);
  }

  public function item_list(Request $request)
  {
    $data=Item::leftJoin('categories','categories.id','=','items.category_id')
    ->select('items.id','items.code','categories.id as id_kategori','items.name as nama_item','categories.name as nama_kategori','items.part_number','items.description','items.part_number','items.barcode','items.initial_cost','items.harga_beli', 'items.harga_jual')
    ->get();
    return Response::json(['status' => 'OK', 'message' => 'List Item','data' => $data],200);
  }

  public function stripping_list(Request $request)
  {
    $data=WarehouseReceipt::leftJoin('contacts','contacts.id','=','warehouse_receipts.customer_id')
    ->leftJoin('warehouses','warehouses.id','=','warehouse_receipts.warehouse_id')
    ->leftJoin('contacts as sender','sender.id','=','warehouse_receipts.sender_id')
    ->leftJoin('contacts as receiver','receiver.id','=','warehouse_receipts.receiver_id')
    ->leftJoin('users','users.id','=','warehouse_receipts.warehouse_staff_id')
    ->select('warehouse_receipts.id','warehouse_receipts.customer_id','contacts.name as customer','warehouse_receipts.warehouse_id','warehouses.name','warehouse_receipts.sender_id','sender.name as sender','warehouse_receipts.receiver_id','receiver.name as receiver','warehouse_receipts.warehouse_staff_id','users.name as staff_gudang','warehouse_receipts.code','warehouse_receipts.reff_no','warehouse_receipts.receive_date','warehouse_receipts.stripping_done','warehouse_receipts.total_qty','warehouse_receipts.is_export','warehouse_receipts.description')
    ->get();
    return Response::json(['status' => 'OK', 'message' => 'List Stripping','data' => $data],200);
  }

}
