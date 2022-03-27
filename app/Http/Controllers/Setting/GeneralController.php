<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Vessel;
use App\Model\Piece;
use App\Model\Commodity;
use App\Model\Service;
use App\Model\ContainerType;
use App\Model\Bank;
use App\Model\CustomerStage;
use App\Model\ServiceType;
use App\Model\Port;
use App\Model\AirPort;
use App\Model\AddressType;
use App\Model\VendorType;
use App\Model\Contact;
use App\Model\KpiStatus;
use App\Model\LeadStatus;
use App\Model\LeadSource;
use App\Model\Industry;
use App\Model\Account;
use App\Model\ServiceGroup;
use Response;
use Image;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class GeneralController extends Controller
{
    /*
      Date : 25-04-2020
      Description : Membuat shipment status
      Developer : Didin
      Status : Create
    */
    function storeRemarkLogo(Request $request) {
        $remark = DB::table('print_remarks')
        ->first();
        File::delete(public_path('files/' . $remark->logo));
        if($request->has('logo')) {
            $file=$request->file('logo');
              $origin = $file->getClientOriginalName();
              $filename = 'LOGOREMARK' . date('Ymd_His') . $origin;
              $img = Image::make($file->getRealPath());
              $img->save(public_path('files/' . $filename));
              $id = DB::table('print_remarks')
              ->whereId($remark->id)
              ->update([
                  'logo' => $filename
              ]);
              $attachments = [
                 'url' => asset('files/' . $filename)
              ];
        } else {
            return Response::json(['message' => 'File wajib dilampirkan'], 500);
        }

        return Response::json(['message' => 'File berhasil di-upload', 'attachments' => $attachments]);
    }  

  public function print_remark()
  {
      $data=DB::table('print_remarks')->first();
      $data->logo = asset('files/' . $data->logo);
      return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }
  
  public function store_remark(Request $request)
  {
    DB::beginTransaction();
    $input=$request->all();
    unset($input['logo']);
    $input['additional'] = json_encode((object)$input['additional']);
    DB::table('print_remarks')->where('id','!=',null)->update($input);
    DB::commit();

    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
  }
  public function vessel($id=null)
  {
    if (isset($id)) {
      $data['item'] = Vessel::find($id);
    }
    $data['vendor']=Contact::whereRaw("is_vendor = 1 and vendor_status_approve = 2")->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function bank($id=null)
  {
    if (isset($id)) {
      $v = Bank::find($id);
    } else {
      $v = Bank::all();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }
  public function customer_stage($id=null)
  {
    if (isset($id)) {
      $v = CustomerStage::find($id);
    } else {
      $v = CustomerStage::all();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_vessel(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $v=Vessel::find($id);
      $request->validate([
        'code' => 'required|unique:vessels,code,'.$id,
        'name' => 'required',
        'vendor_id' => 'required',
      ]);
    } else {
      $v=new Vessel;
      $request->validate([
        'code' => 'required|unique:vessels,code',
        'name' => 'required',
        'vendor_id' => 'required',
      ]);
    }
    $v->code=$request->code;
    $v->name=$request->name;
    $v->vendor_id=$request->vendor_id;
    $v->save();
    DB::commit();

    $vessel = Vessel::where('code', $request->code)->first();


    return Response::json($vessel, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_countries(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $request->validate([
        'name' => 'required',
      ]);

      DB::table('countries')->where('id', $id)->update([
        'name' => $request->name
      ]);
    } else {
      $request->validate([
        'name' => 'required',
      ]);

      DB::table('countries')->insert([
        'name' => $request->name
      ]);
    }
    
    DB::commit();

    return Response::json(null);
  }
  public function store_bank(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $v=Bank::find($id);
    } else {
      $v=new Bank;
    }
    $v->code=$request->code;
    $v->name=$request->name;
    $v->save();
    DB::commit();


    return Response::json(null);
  }
  public function store_customer_stage(Request $request, $id=null)
  {
    if ($request->is_close_deal==1) {
      $cek=CustomerStage::where('is_close_deal', 1)->count();
      if ($cek>0) {
        return Response::json(['message' => 'Close Deal sudah dicentang di customer stage lainnya.'],500);
      }
    }
    if ($request->is_prospect==1) {
      $cek=CustomerStage::where('is_prospect', 1)->count();
      if ($cek>0) {
        return Response::json(['message' => 'Prospek sudah dicentang di customer stage lainnya.'],500);
      }
    }
    if ($request->is_negotiation==1) {
      $cek=CustomerStage::where('is_negotiation', 1)->count();
      if ($cek>0) {
        return Response::json(['message' => 'Negosiasi sudah dicentang di customer stage lainnya.'],500);
      }
    }
    DB::beginTransaction();
    if (isset($id)) {
      $v=CustomerStage::find($id);
    } else {
      $v=new CustomerStage;
    }
    $v->bobot=$request->bobot;
    $v->name=$request->name;
    $v->is_close_deal=$request->is_close_deal;
    $v->is_prospect=$request->is_prospect;
    $v->is_negotiation=$request->is_negotiation;
    $v->save();
    DB::commit();

    return Response::json(null);
  }
  public function satuan($id=null)
  {
    if (isset($id)) {
      $v = Piece::find($id);
    } else {
      $v = DB::table('pieces')
      ->select('id', 'name')
      ->get();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function commodity($id=null)
  {
    if (isset($id)) {
      $v = Commodity::find($id);
    } else {
      $v = DB::table('commodities')
      ->get();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_commodity(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $v=Commodity::find($id);
    } else {
      $v=new Commodity;
    }
    // $v->code=$request->code;
    $v->name=$request->name;
    $v->is_default=$request->is_default;
    $v->is_expired=$request->is_expired;
    $v->save();
    DB::commit();

    return Response::json(null);
  }
  
    /*
      Date : 29-08-2020
      Description : Menampilkan daftar layanan atau detail layanan
      Developer : Didin
      Status : Edit
    */
  public function service($id=null)
  {
        if (isset($id)) {
          $data['item'] = Service::find($id);
        } else {
          $data = DB::table('services')
          ->select('id', 'name', 'service_type_id')
          ->get();
        }
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

    /*
      Date : 29-08-2020
      Description : Menampilkan daftar tipe layanan
      Developer : Didin
      Status : Create
    */
  public function serviceType()
  {
        $data = DB::table('service_types')
          ->select('id', 'name')
          ->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }


    /*
      Date : 29-08-2020
      Description : Menampilkan daftar akun
      Developer : Didin
      Status : Create
    */
  public function account()
  {
        $data = DB::table('accounts')
          ->leftJoin('accounts AS parents', 'parents.id', 'accounts.parent_id')
          ->where('accounts.is_base', 0)
          ->select('accounts.id', 'accounts.name', DB::raw('CONCAT("{\"name\" : \"", parents.name , "\"}") AS parent'))
          ->get();

        $data = $data->map(function($x){
            $x->parent = json_decode($x->parent);
            return $x;
        });
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }


    /*
      Date : 29-08-2020
      Description : Menampilkan daftar kelompok layanan
      Developer : Didin
      Status : Create
    */
  public function serviceGroup()
  {
        $data = DB::table('service_groups')
          ->select('id', 'name')
          ->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

    /*
      Date : 29-08-2020
      Description : Menampilkan daftar kelompok layanan
      Developer : Didin
      Status : Create
    */
  public function moda()
  {
        $data = DB::table('modas')
          ->select('id', 'name')
          ->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function indexCommodity()
  {
    $data['commodity'] = DB::table('commodities')
    ->select('id', 'name')
    ->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_service(Request $request, $id=null)
  {
    $request->validate([
      'name' => 'required',
      'service_type_id' => 'required',
      'service_group_id' => 'required'
    ],[
      'service_type_id.required' => 'Kolom Jenis Layanan Wajib diisi!',
      'service_group_id.required' => 'Kolom Golongan Layanan Wajib diisi!'
    ]);
    DB::beginTransaction();
    if (isset($id)) {
      $v=Service::find($id);
    } else {
      $v=new Service;
    }
    // $v->code=$request->code;
    $v->name=$request->name;
    $v->description=$request->description;
    if($request->service_type_id == 12 || $request->service_type_id == 13) {
        $v->is_overtime=$request->is_overtime ?? 0;
    } else{
        $v->is_overtime = 0;
    }
    $v->service_type_id=$request->service_type_id;
    $v->service_group_id=$request->service_group_id;
    $v->account_sale_id=$request->account_sale_id;
    $v->kpi_status_id=$request->kpi_status_id;
    $v->is_default=($request->is_default?:0);
    $v->save();

    if (empty($id)) {
      KpiStatus::create([
        'service_id' => $v->id,
        'create_by' => auth()->id(),
        'sort_number' => 1,
        'name' => 'Job Order',
        'duration' => 1,
        'is_core' => 1
      ]);
      KpiStatus::create([
        'service_id' => $v->id,
        'create_by' => auth()->id(),
        'sort_number' => 2,
        'name' => 'Finish',
        'duration' => 1,
        'is_done' => 1,
        'status' => 4
      ]);
    }
    DB::commit();

    return Response::json(null);
  }

  public function store_service_warehouse(Request $request, $id=null)
  {
    
    DB::beginTransaction();
    if (isset($id)) {
      $v=Service::find($id);
    } else {
      $v=new Service;
    }
    // $v->code=$request->code;
    $v->description=$request->description;
    $v->account_sale_id=$request->account_sale_id;
    $v->save();

    DB::commit();

    return Response::json(null);
  }
  public function container($id=null)
  {
    if (isset($id)) {
      $v = ContainerType::find($id);
    } else {
      $v = ContainerType::all();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function delete_vessel($id)
  {
    DB::beginTransaction();
    try {
      Vessel::find($id)->delete();
      DB::commit();
    } catch (Exception $e) {
        DB::rollback();
        return Response::json(['message' => 'Kapal sudah yang sudah digunakan tidak dapat dihapus'], 421);
    }

    return Response::json(null);
  }
  public function delete_countries($id)
  {
    DB::beginTransaction();
    try {
      DB::table('countries')->where('id', $id)->delete();
      DB::commit();
    } catch(Exception $e) {
      DB::rollback();
      return Response::json(['message' => 'Negara sudah yang sudah digunakan tidak dapat dihapus'], 421);
    }

    return Response::json(null);
  }
  
  public function delete_commodity($id)
  {
    DB::beginTransaction();
    Commodity::find($id)->delete();
    DB::commit();

    return Response::json(null);
  }
  
  public function delete_service($id)
  {
    DB::beginTransaction();
    Service::find($id)->delete();
    DB::commit();

    return Response::json(null);
  }
  public function delete_bank($id)
  {
    DB::beginTransaction();
    try {
      Bank::find($id)->delete();
      DB::commit();
    } catch(Exception $e) {
        DB::rollback();
        return Response::json(['message' => 'Bank sudah yang sudah digunakan tidak dapat dihapus'], 421);
    }

    return Response::json(null);
  }
  public function delete_port($id)
  {
    DB::beginTransaction();
    Port::find($id)->delete();
    DB::commit();

    return Response::json(null);
  }
  public function delete_vendor_type($id)
  {
    DB::beginTransaction();
    VendorType::find($id)->delete();
    DB::commit();

    return Response::json(null);
  }
  public function delete_address_type($id)
  {
    DB::beginTransaction();
    AddressType::find($id)->delete();
    DB::commit();

    return Response::json(null);
  }
  public function delete_airport($id)
  {
    DB::beginTransaction();
    AirPort::find($id)->delete();
    DB::commit();

    return Response::json(null);
  }
  public function delete_customer_stage($id)
  {
    DB::beginTransaction();
    CustomerStage::find($id)->delete();
    DB::commit();

    return Response::json(null);
  }

  public function port($id=null)
  {
    if (isset($id)) {
      $v = Port::find($id);
    } else {
      $v['port'] = DB::table('ports')->selectRaw('id,name')->get();
      $v['country'] = DB::table('countries')->selectRaw('id,name')->get();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_port(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $request->validate([
        'code' => 'required|unique:ports,code,'.$id,
        'name' => 'required',
        'island_name' => 'required',
        'country_id' => 'required',
      ]);
      $v=Port::find($id);
    } else {
      $request->validate([
        'code' => 'required|unique:ports,code',
        'name' => 'required',
        'island_name' => 'required',
        'country_id' => 'required',
      ]);
      $v=new Port;
    }
    // $v->code=$request->code;
    $v->name=$request->name;
    $v->code=$request->code;
    $v->island_name=$request->island_name;
    $v->country_id=$request->country_id;
    $v->save();
    DB::commit();

    return Response::json(null);
  }
  public function airport($id=null)
  {
    if (isset($id)) {
      $v = AirPort::find($id);
    } else {
      $v = AirPort::all();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_airport(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $request->validate([
        'code' => 'required|unique:air_ports,code,'.$id,
        'name' => 'required',
        'island_name' => 'required',
      ]);
      $v=AirPort::find($id);
    } else {
      $request->validate([
        'code' => 'required|unique:air_ports,code',
        'name' => 'required',
        'island_name' => 'required',
      ]);
      $v=new AirPort;
    }
    // $v->code=$request->code;
    $v->name=$request->name;
    $v->code=$request->code;
    $v->island_name=$request->island_name;
    $v->save();
    DB::commit();

    return Response::json(null);
  }

  public function address_type($id=null)
  {
    if (isset($id)) {
      $v = AddressType::find($id);
    } else {
      $v = AddressType::all();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_address_type(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $request->validate([
        'name' => 'required',
      ]);
      $v=AddressType::find($id);
    } else {
      $request->validate([
        'name' => 'required',
      ]);
      $v=new AddressType;
    }
    // $v->code=$request->code;
    $v->name=$request->name;
    $v->save();
    DB::commit();

    return Response::json(null);
  }

  public function vendor() {
      $vendor = DB::table('contacts')
      ->where('is_vendor',1)
      ->where('vendor_status_approve', 2)
      ->select('id', 'name')
      ->get();

      return Response::json($vendor, 200, [], JSON_NUMERIC_CHECK);
  }

  public function vendor_type($id=null)
  {
    if (isset($id)) {
      $v = VendorType::find($id);
    } else {
      $v = VendorType::all();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_vendor_type(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $request->validate([
        'name' => 'required',
      ]);
      $v=VendorType::find($id);
    } else {
      $request->validate([
        'name' => 'required',
      ]);
      $v=new VendorType;
    }
    // $v->code=$request->code;
    $v->name=$request->name;
    $v->save();
    DB::commit();

    return Response::json(null);
  }

  public function lead_status($id=null)
  {
    if (isset($id)) {
      $v = LeadStatus::find($id);
    } else {
      $v = LeadStatus::all();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_lead_status(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $request->validate([
        'status' => 'required',
        'is_active' => 'required',
      ]);
      $v=LeadStatus::find($id);
    } else {
      $request->validate([
        'status' => 'required',
        'is_active' => 'required',
      ]);
      $v=new LeadStatus;
    }
    // $v->code=$request->code;
    $v->status=$request->status;
    $v->is_active=$request->is_active;
    $v->save();
    DB::commit();

    return Response::json(null);
  }
  public function lead_source($id=null)
  {
    if (isset($id)) {
      $v = LeadSource::find($id);
    } else {
      $v = LeadSource::all();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_lead_source(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $request->validate([
        'name' => 'required',
        'is_active' => 'required',
      ]);
      $v=LeadSource::find($id);
    } else {
      $request->validate([
        'name' => 'required',
        'is_active' => 'required',
      ]);
      $v=new LeadSource;
    }
    $v->name=$request->name;
    $v->is_active=$request->is_active;
    $v->save();
    DB::commit();

    return Response::json(null);
  }
  public function industry($id=null)
  {
    if (isset($id)) {
      $v = Industry::find($id);
    } else {
      $v = Industry::all();
    }
    return Response::json($v, 200, [], JSON_NUMERIC_CHECK);
  }

  public function store_industry(Request $request, $id=null)
  {
    DB::beginTransaction();
    if (isset($id)) {
      $request->validate([
        'code' => 'required|unique:industries,code,'.$id,
        'name' => 'required',
        'is_active' => 'required',
      ]);
      $v=Industry::find($id);
    } else {
      $request->validate([
        'code' => 'required|required',
        'name' => 'required',
        'is_active' => 'required',
      ]);
      $v=new Industry;
      $v->create_by=auth()->id();
    }
    $v->code=$request->code;
    $v->name=$request->name;
    $v->is_active=$request->is_active;
    $v->save();
    DB::commit();

    return Response::json(null);
  }

}
