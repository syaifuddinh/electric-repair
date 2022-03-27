<?php

namespace App\Http\Controllers\Api\v4\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Rack;
use App\Model\Warehouse;
use App\Model\Company;
use App\Model\Category;
use App\Model\StorageType;
use App\Model\VehicleType;
use App\Model\Contact;
use App\Model\Picking;
use App\Model\Packaging;
use App\Model\ItemMigration;
use App\Model\StokOpnameWarehouse;
use App\Model\CostType;
use Milon\Barcode\DNS1D;
use DB;
use Response;
use Exception;

class SettingController extends Controller
{
  public function rack()
  {
    $data['warehouse']=Warehouse::all();
    $data['storage_type']=StorageType::all();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function warehouse()
  {
    $data['company']=companyAdmin(auth()->id());
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function list_warehouse(Request $request)
  {
    $w=Warehouse::where('is_active', 1)->select('id', 'code', 'name', 'company_id');

    if(isset($request->keyword)) {
      $w = $w->whereRaw("(`name` LIKE '%$request->keyword%' OR `code` LIKE '%$request->keyword%')");
    }

    if(isset($request->company_id)) {
      $w = $w->where("company_id", $request->company_id);
    }

    $data['warehouse'] = $w->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }


  public function list_kpi_status(Request $request)
  {
    $kpi_status=DB::table('kpi_statuses')->whereRaw('1=1')->select('id', 'name', 'is_done')->orderBy('sort_number','asc ');

    if(isset($request->keyword)) {
      $kpi_status = $kpi_status->whereRaw("`name` LIKE '%$request->keyword%'");
    }
    if(isset($request->is_handling)) {
      if($request->is_handling == 1) {
        $s = DB::table('services')->where('is_warehouse', 1)->whereRaw('`name` LIKE "%handling%"')->first();
        $kpi_status = $kpi_status->where('service_id', $s->id);
      }
    }
    if(isset($request->is_stuffing)) {
      if($request->is_stuffing == 1) {
        $s = DB::table('services')->where('is_warehouse', 1)->whereRaw('`name` LIKE "%stuffing%"')->first();
        $kpi_status = $kpi_status->where('service_id', $s->id);
      }
    }

    $data['kpi'] = $kpi_status->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  
  public function list_company(Request $request)
  {
    $c=Company::whereRaw('1=1')->select('id', 'code', 'name');

    if(isset($request->keyword)) {
      $c = $c->whereRaw("(`name` LIKE '%$request->keyword%' OR `code` LIKE '%$request->keyword%')");
    }

    $data['company'] = $c->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function list_rack(Request $request)
  {
    $r=Rack::whereRaw('1=1')->select('id', 'code', 'warehouse_id');

    if(isset($request->keyword)) {
      $r = $r->whereRaw("`code` LIKE '%$request->keyword%'");
    }

    if(isset($request->id)) {
      $r = $r->where('racks.id', $request->id);
    }

    if( $request->filled('warehouse_id') ) {
      $r = $r->where('warehouse_id', $request->warehouse_id);
    }

    $data['rack'] = $r->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  /*
      Date : 16-04-2020
      Description : Daftar storage type
      Developer : Dimas
      Status : Create
  */
  public function listStorageType(Request $request)
  {
    $r=DB::table('storage_types')
    ->select('id', 'name');

    if(isset($request->keyword)) {
      $r = $r->whereRaw("`name` LIKE '%$request->keyword%'");
    }

    $data['storage_type'] = $r->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }


  public function list_staff_gudang(Request $request)
  {
    $r=Contact::whereRaw("is_staff_gudang = 1")->select('id','name','address');

    if(isset($request->keyword)) {
      $r = $r->whereRaw("`name` LIKE '%$request->keyword%'");
    }

    $data['staff_gudang'] = $r->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function list_cost_type(Request $request)
  {
    $r=CostType::with('parent:id,name')->where('company_id', $request->company_id)->where('parent_id','!=',null)->selectRaw('id, vendor_id, qty, cost, initial_cost, parent_id');

    if(isset($request->keyword)) {
      $r = $r->whereRaw("`name` LIKE '%$request->keyword%'");
    }

    $data['cost_type'] = $r->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function list_vehicle(Request $request)
  {
    $r=VehicleType::select('id','name');

    if(isset($request->keyword)) {
      $r = $r->whereRaw("`name` LIKE '%$request->keyword%'");
    }

    $data['vehicle'] = $r->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function delete_rack($id)
  {
    DB::beginTransaction();
    Rack::find($id)->delete();
    DB::commit();
  }
  public function delete_warehouse($id)
  {
    DB::beginTransaction();
    Warehouse::find($id)->delete();
    DB::commit();
  }

  public function store_rack(Request $request)
  {
    $request->validate([
      'warehouse_id' => 'required',
      'code' => 'required',
      'storage_type_id' => 'required',
      'capacity_tonase' => 'required',
      'capacity_volume' => 'required',
    ],[
      'storage_type_id.required' => 'Type Storage harus diisi'
    ]);
    DB::beginTransaction();
    if ($request->id) {
      Rack::find($request->id)->update([
        'warehouse_id' => $request->warehouse_id,
        'code' => $request->code,
        'barcode' => $request->barcode,
        'storage_type_id' => $request->storage_type_id,
        'capacity_volume' => $request->capacity_volume,
        'capacity_tonase' => $request->capacity_tonase,
      ]);
    } else {
      Rack::create([
        'warehouse_id' => $request->warehouse_id,
        'code' => $request->code,
        'barcode' => $request->barcode,
        'storage_type_id' => $request->storage_type_id,
        'capacity_volume' => $request->capacity_volume,
        'capacity_tonase' => $request->capacity_tonase,
      ]);
    }
    DB::commit();
  }
  public function store_warehouse(Request $request)
  {
    DB::beginTransaction();
    if ($request->id) {
      $request->validate([
        'company_id' => 'required',
        'code' => 'required|unique:warehouses,code,'.$request->id,
        'name' => 'required',
        'capacity_tonase' => 'required',
        'capacity_volume' => 'required',
      ]);

      Warehouse::find($request->id)->update([
        'company_id' => $request->company_id,
        'code' => $request->code,
        'name' => $request->name,
        'address' => $request->address,
        'capacity_volume' => $request->capacity_volume,
        'capacity_tonase' => $request->capacity_tonase,
      ]);
    } else {
      $request->validate([
        'company_id' => 'required',
        'code' => 'required|unique:warehouses,code',
        'name' => 'required',
        'capacity_tonase' => 'required',
        'capacity_volume' => 'required',
      ]);

      $city_id = Company::find($request->company_id)->city_id;
      Warehouse::create([
        'company_id' => $request->company_id,
        'code' => $request->code,
        'name' => $request->name,
        'city_id' => $city_id,
        'warehouse_type_id' => 1,
        'address' => $request->address,
        'capacity_volume' => $request->capacity_volume,
        'capacity_tonase' => $request->capacity_tonase,
      ]);
    }
    DB::commit();
  }

  public function category_pallet_list()
  {
    $data['category']=DB::table('categories')->whereRaw('is_pallet = 1 and parent_id is null')->get();
    return Response::json($data,200);
  }

  public function store_pallet_category(Request $request)
  {
    DB::beginTransaction();
    if ($request->id) {
      $request->validate([
        'code' => 'required|unique:categories,code,'.$request->id,
        'name' => 'required'
      ]);
      $input=$request->except('id');
      Category::find($request->id)->update($input);
    } else {
      $request->validate([
        'code' => 'required|unique:categories,code',
        'name' => 'required'
      ]);
      $input=$request->all();
      Category::create($input);
    }
    DB::commit();

    return Response::json(null,200);
  }

  public function store_storage_type(Request $request)
  {
    DB::beginTransaction();
    if ($request->id) {
      $request->validate([
        'name' => 'required'
      ]);
      $input=$request->except('id');
      StorageType::find($request->id)->update($input);
    } else {
      $request->validate([
        'name' => 'required'
      ]);
      $input=$request->all();
      StorageType::create($input);
    }
    DB::commit();

    return Response::json(null,200);
  }

  /*
      Date : 15-04-2020
      Description : API Mendapatkan informasi yang dibutuhkan oleh dashboard
      Developer : Dimas
      Status : Create
  */
  public function dashboard(){
    $receipts_qty = DB::table('warehouse_receipts')->get()->count();
    $receipts = array(
      'qty' => $receipts_qty,
      'piece' => 'Jobs',
      'label' => 'Good Receiving'
    );
    
    $stocklists_qty = DB::table('stock_transactions_report')->join('stock_transactions', 'stock_transactions.id', 'stock_transactions_report.header_id')
    ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'stock_transactions.warehouse_receipt_detail_id')
    ->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
    ->join('warehouses', 'warehouses.id', 'stock_transactions_report.warehouse_id')
    ->join('items', 'items.id', 'stock_transactions_report.item_id')
    ->join('contacts', 'warehouse_receipts.customer_id', 'contacts.id')
    ->selectRaw('SUM(stock_transactions_report.qty_masuk - stock_transactions_report.qty_keluar) AS qty')
    ->first()
    ->qty;
    $stocklists = array(
      'qty' => $stocklists_qty,
      'piece' => 'Items',
      'label' => 'Inventory Stock'
    );

    $contacts_qty = DB::table('contacts')->get()->count();
    $contacts = array(
      'qty' => $contacts_qty,
      'piece' => 'Contacts',
      'label' => 'Contacts'
    );

    $putaway_qty = ItemMigration::with('warehouse_from:id,name', 'rack_from:id,code', 'rack_to:id,code')
    ->join('item_migration_details', 'item_migrations.id', 'item_migration_details.header_id')
    ->groupBy('item_migrations.id')
    ->whereRaw('warehouse_from_id = warehouse_to_id')
    ->get()->count();
    $putaway = array(
      'qty' => $putaway_qty,
      'piece' => 'Jobs',
      'label' => 'PutAway'
    );

    $transfers_qty = ItemMigration::with('warehouse_from:id,name','warehouse_to:id,name', 'rack_from:id,code', 'rack_to:id,code')
    ->join('item_migration_details', 'item_migrations.id', 'item_migration_details.header_id')
    ->groupBy('item_migrations.id')
    ->whereRaw('warehouse_from_id != warehouse_to_id')
    ->get()->count();
    $transfers = array(
      'qty' => $transfers_qty,
      'piece' => 'Jobs',
      'label' => 'Transfer'
    );

    $adjustment_qty = StokOpnameWarehouse::all()->count();
    $adjustment = array(
      'qty' => $adjustment_qty,
      'piece' => 'Items',
      'label' => 'Adjustments'
    );

    $picking_qty = Picking::all()->count();
    $pickings = array(
      'qty' => $picking_qty,
      'piece' => 'Jobs',
      'label' => 'Picking'
    );

    $packaging_qty = Packaging::all()->count();
    $packaging = array(
      'qty' => $packaging_qty,
      'piece' => 'Jobs',
      'label' => 'Packaging'
    );

    $data = array(
      'receipts' => $receipts,
      'stocklist' => $stocklists,
      'contacts' => $contacts,
      'putaway' => $putaway,
      'transfers' => $transfers,
      'adjustment' => $adjustment,
      'pickings' => $pickings,
      'packaging' => $packaging
    );

    // dd($data);
    return Response::json(['status' => 'OK', 'data' => $data],200,[],JSON_NUMERIC_CHECK);
  }

  /*
      Date : 15-04-2020
      Description : API Mendapatkan jumlah warehouse di menu setting
      Developer : Dimas
      Status : Create
  */
  public function warehouse_amount(){
    $data['qty'] = DB::table('warehouses')->get()->count();

    return Response::json(['status' => 'OK', 'data' => $data],200,[],JSON_NUMERIC_CHECK);
  }

  /*
      Date : 15-04-2020
      Description : API Mendapatkan jumlah operator di menu setting
      Developer : Dimas
      Status : Create
  */
  public function operator_amount(){
    $data['qty'] = DB::table('contacts')
    ->where('is_staff_gudang', 1)
    ->get()->count();

    return Response::json(['status' => 'OK', 'data' => $data],200,[],JSON_NUMERIC_CHECK);
  }

  /*
      Date : 15-04-2020
      Description : API Mendapatkan detail warehouse
      Developer : Dimas
      Status : Create
  */
  public function detail_warehouse($id){
    $data = DB::table('warehouses')
    ->where('id', $id)
    ->select('id', 'name', 'address', 'capacity_volume', 'capacity_tonase')
    ->first();

    if(!Empty($data)){
      return Response::json(['status' => 'OK', 'message' => 'Data ditemukan!' ,'data' => $data],200,[],JSON_NUMERIC_CHECK);
    }
    else{
      return Response::json(['status' => 'ERROR', 'message' => 'Data tidak ditemukan!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
  }

  /*
      Date : 15-04-2020
      Description : API Mendapatkan semua rack pada warehouse tertentu {id}
      Developer : Dimas
      Status : Create
  */
  public function get_rack_warehouse($id){
    $warehouse = DB::table('warehouses')
    ->where('id', $id)
    ->first();

    if($warehouse == null ) {      
      return Response::json(['status' => 'ERROR', 'message' => 'Gudang tidak ditemukan!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }

    $data['rack'] = DB::table('racks')
    ->where('warehouse_id', $id)
    ->select('id', 'code', 'created_at')
    ->get();

    if(!Empty($data['rack'])){
      return Response::json(['status' => 'OK', 'message' => 'Data ditemukan!' ,'data' => $data],200,[],JSON_NUMERIC_CHECK);
    }
    else{
      return Response::json(['status' => 'ERROR', 'message' => 'Data tidak ditemukan!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
  }

  /*
      Date : 15-04-2020
      Description : API Menambahkan rack pada warehouse
      Developer : Dimas
      Status : Create
  */
  public function add_rack_warehouse(Request $request){
    if(!$request->filled('warehouse_id')) {      
      return Response::json(['status' => 'ERROR', 'message' => 'Gudang tidak boleh kosong', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
    if(!$request->filled('code')) {      
      return Response::json(['status' => 'ERROR', 'message' => 'Kode tidak boleh kosong', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
    if(!$request->filled('storage_type_id')) {      
      return Response::json(['status' => 'ERROR', 'message' => 'Tipe penyimpanan tidak boleh kosong', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
      
    $cek = DB::table('warehouses')
    ->where('id', $request->warehouse_id)->first();
  
    if(Empty($cek)){
      return Response::json(['status' => 'ERROR', 'message' => 'Gudang tidak ditemukan!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }

    $cek = DB::table('storage_types')
    ->where('id', $request->storage_type_id)->first();
    
    if(Empty($cek)){
      return Response::json(['status' => 'ERROR', 'message' => 'Tipe Penyimpanan tidak ada!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
    
    $barcode = "";

    if(isset($request->barcode)){
      $barcode = $request->barcode;
    }
    // generate random string + number for barcode
    else{
      $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $numbers = '0123456789'; 
      $randomString = ''; 
    
      for ($i = 0; $i < 3; $i++) { 
          $index = rand(0, strlen($characters) - 1); 
          $randomString .= $characters[$index]; 
      }
      
      for ($i = 0; $i < 3; $i++) { 
        $index = rand(0, strlen($numbers) - 1); 
        $randomString .= $numbers[$index]; 
      }

      $barcode = $randomString;
    }

    DB::beginTransaction();

    DB::table('racks')->insert([
      'warehouse_id' => $request->warehouse_id,
      'barcode' => $barcode,
      'code' => $request->code,
      'storage_type_id' => $request->storage_type_id,
      'capacity_volume' => $request->capacity_volume ?? 0,
      'capacity_tonase' => $request->capacity_tonase ?? 0 
    ]);

    DB::commit();

    return Response::json(['status' => 'OK', 'message' => 'Rak penyimpanan berhasil ditambahkan' ,'data' =>null],200,[],JSON_NUMERIC_CHECK);
  }

  /*
      Date : 15-04-2020
      Description : API Mengupdate rack pada warehouse {id}
      Developer : Dimas
      Status : Create
  */
  public function update_rack_warehouse(Request $request, $id){
    
    $cek = DB::table('racks')
    ->where('id', $id)->first();

    if(Empty($cek)){
      return Response::json(['status' => 'ERROR', 'message' => 'Rak penyimpanan tidak ditemukan!', 'data' => null], 422, [], JSON_NUMERIC_CHECK);
    }

    if(!$request->filled('warehouse_id')) {      
      return Response::json(['status' => 'ERROR', 'message' => 'Gudang tidak boleh kosong', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
    if(!$request->filled('code')) {      
      return Response::json(['status' => 'ERROR', 'message' => 'Kode tidak boleh kosong', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
    if(!$request->filled('storage_type_id')) {      
      return Response::json(['status' => 'ERROR', 'message' => 'Tipe penyimpanan tidak boleh kosong', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
     

    $cek = DB::table('warehouses')
    ->where('id', $request->warehouse_id)->first();
  
    if(Empty($cek)){
      return Response::json(['status' => 'ERROR', 'message' => 'Gudang tidak ditemukan!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
    
    $cek = DB::table('storage_types')
    ->where('id', $request->storage_type_id)->first();

    if(Empty($cek)){
      return Response::json(['status' => 'ERROR', 'message' => 'Tipe Penyimpanan tidak ada!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
    DB::beginTransaction();

    DB::table('racks')->where('id', $id)->update([
      'warehouse_id' => $request->warehouse_id,
      'code' => $request->code,
      'barcode' => $request->barcode,
      'storage_type_id' => $request->storage_type_id,
      'capacity_volume' => $request->capacity_volume,
      'capacity_tonase' => $request->capacity_tonase 
    ]);

    DB::commit();

    return Response::json(['status' => 'OK', 'message' => 'Rak penyimpanan berhasil diupdate' ,'data' => null],200,[],JSON_NUMERIC_CHECK);
  }

  /*
      Date : 15-04-2020
      Description : API Delete rack pada warehouse {id}
      Developer : Dimas
      Status : Create
  */
  public function delete_rack_warehouse($id){
    $cek = DB::table('racks')
    ->where('id', $id)->first();

    if(Empty($cek)){
      return Response::json(['status' => 'ERROR', 'message' => 'Rak penyimpanan tidak ditemukan!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }

    DB::beginTransaction();
    try {
        $cek = DB::table('racks')
        ->where('id', $id)->delete();

        DB::commit();
    } catch (Exception $e) {
        DB::rollback();
        if($e->getCode() == 23000) {      
            return Response::json(['status' => 'ERROR', 'message' => 'Rak penyimpanan tidak bisa dihapus karena sudah digunakan pada penerimaan barang', 'data' => null],422,[],JSON_NUMERIC_CHECK);
        } 
        return Response::json(['status' => 'ERROR', 'message' => $e->getMessage(), 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }

    return Response::json(['status' => 'OK', 'message' => 'Rak penyimpanan berhasil dihapus' ,'data' => null],200,[],JSON_NUMERIC_CHECK);
  }

  /*
      Date : 16-04-2020
      Description : API Menampilkan sebuah rack warehouse
      Developer : Dimas
      Status : Create
  */
  public function show_rack_warehouse($id_rack){

    $cek = DB::table('racks')
    ->where('id', $id_rack)->first();

    if(Empty($cek)){
      return Response::json(['status' => 'ERROR', 'message' => 'Rak penyimpanan tidak ditemukan!', 'data' => null], 422, [], JSON_NUMERIC_CHECK);
    }
    
    $data = DB::table('racks AS r')
    ->leftJoin('storage_types AS st', 'st.id', '=', 'r.storage_type_id')
    ->leftJoin('warehouses AS w', 'w.id', '=', 'r.warehouse_id')
    ->where('r.id', $id_rack)
    ->selectRaw('
      r.id AS id,
      r.code AS code,
      r.barcode AS barcode,
      r.storage_type_id AS storage_type_id,
      st.name AS storage_type_name,
      r.warehouse_id AS warehouse_id,
      w.name AS warehouse_name,
      w.address AS address,
      r.capacity_volume AS capacity_volume,
      r.capacity_tonase AS capacity_tonase
    ')
    ->first();

    if(!Empty($data)){
      return Response::json(['status' => 'OK', 'message' => 'Data ditemukan!' ,'data' => $data],200,[],JSON_NUMERIC_CHECK);
    }
    else{
      return Response::json(['status' => 'ERROR', 'message' => 'Data tidak ditemukan!', 'data' => null],422,[],JSON_NUMERIC_CHECK);
    }
  }
}
