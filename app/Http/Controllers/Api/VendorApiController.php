<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Contact;
use App\Model\VendorPrice;
use App\Model\Vehicle;
use App\Model\VehicleContact;
use App\Model\DeliveryOrderDriver;
use App\Model\Manifest;
use App\Model\JobStatusHistory;
use App\User;
use DataTables;
use DB;
use Auth;
use Response;

class VendorApiController extends Controller
{
  public function vendor_datatable()
  {
    $item = Contact::leftJoin('cities','cities.id','=','contacts.city_id')
            ->where('is_vendor', 1)
            ->where('vendor_status_approve', 2)
            ->select('contacts.*',DB::raw("CONCAT(cities.type,' ',cities.name) as cityname"),DB::raw("IFNULL(contacts.code,'-') as codes"));

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('vendor.vendor.detail')\" ui-sref=\"vendor.vendor.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->addColumn('action_fr_contact', function($item){
        $html="<a ng-show=\"roleList.includes('vendor.vendor.detail')\" ui-sref=\"contact.vendor.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->filterColumn('codes', function($query, $keyword) {
          $sql = "IFNULL(contacts.code,'-') like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->filterColumn('cityname', function($query, $keyword) {
          $sql = "CONCAT(cities.type,' ',cities.name) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->rawColumns(['action','action_fr_contact'])
      ->make(true);
  }

  public function register_vendor_datatable()
  {
    $item = Contact::leftJoin('cities','cities.id','=','contacts.city_id')
            ->where('is_vendor', 1)
            ->where('is_active', 1)
            ->where('vendor_status_approve', 1)
            ->select('contacts.*',DB::raw("CONCAT(cities.type,' ',cities.name) as cityname"),DB::raw("IFNULL(contacts.code,'-') as codes"));

    return DataTables::of($item)
      ->editColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('vendor.register.detail')\" ui-sref=\"vendor.register_vendor.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('vendor.register.delete')\" ng-click='deletes($item->id)'><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->filterColumn('codes', function($query, $keyword) {
          $sql = "IFNULL(contacts.code,'-') like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->filterColumn('cityname', function($query, $keyword) {
          $sql = "CONCAT(cities.type,' ',cities.name) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->rawColumns(['action'])
      ->make(true);
  }

  /*
    Date : 02-03-2020
    Description : Menampilkan tarif vendor dalam format datatable
    Developer : Didin
    Status : Edit
  */
    public function vendor_price_datatable(Request $request, $id=null)
    {
        $wr="1=1";

        if (isset($id)) {
            $wr.=" AND vendor_prices.vendor_id = $id";
        }

        if ($request->vendor_id) {
            $wr.=" AND vendor_prices.vendor_id = $request->vendor_id";
        }

        if ($request->company_id) {
            $wr.=" AND vendor_prices.company_id = $request->company_id";
        } else {
            if (auth()->user()->is_admin==0) {
                $wr.=" AND vendor_prices.company_id = ".auth()->user()->company_id;
            }
        }

        $user = $request->user();

        // $item = VendorPrice::with('vendor','company','commodity','service','piece','route','moda','vehicle_type','service_type')->whereRaw($wr)->select('vendor_prices.*');
        $item = DB::table('vendor_prices')
        ->leftJoin('contacts','contacts.id','vendor_prices.vendor_id')
        ->leftJoin('companies','companies.id','vendor_prices.company_id')
        ->leftJoin('routes','routes.id','vendor_prices.route_id')
        ->leftJoin('cost_types','cost_types.id','vendor_prices.cost_type_id')
        ->leftJoin('vehicle_types','vehicle_types.id','vendor_prices.vehicle_type_id')
        ->leftJoin('container_types','container_types.id','vendor_prices.container_type_id')
        ->whereRaw($wr)
        ->whereIsUsed(1)
        ->selectRaw('
          vendor_prices.id,
          vendor_prices.date,
          vendor_prices.vendor_id,
          vendor_prices.cost_category,
          vendor_prices.price_full,
          if(vehicle_types.name is not null, vehicle_types.name, container_types.code) as vtype,
          cost_types.name as cost_type_name,
          cost_types.id as cost_type_id,
          routes.name as trayek,
          companies.name as cabang,
          contacts.name as vendor
          ');

        return DataTables::of($item)
        ->filterColumn('vtype', function($query, $keyword) {
            $sql="if(vehicle_types.name is not null, vehicle_types.name, container_types.code) like ?";
            $query->whereRaw($sql, ["%{$keyword}%"]);
        })
        ->editColumn('price_full', function($item){
            return number_format($item->price_full);
        })
        ->addColumn('action', function($item){
            $html="<a ui-sref=\"marketing.vendor_price.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            $html.="<a ui-sref=\"vendor.register_vendor.show.price.edit({id:$item->vendor_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
            $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->addColumn('action_fr_contact', function($item){
            $html="<a ui-sref=\"contact.vendor.show.price.edit({id:$item->vendor_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
            $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->addColumn('action_approve', function($item){
            $html="<a ui-sref=\"vendor.vendor.show.price.edit({id:$item->vendor_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
            $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->rawColumns(['action','action_approve','action_fr_contact'])
        ->make(true);
    }

  public function add_vehicle()
  {
    $data['vehicle_owner']=DB::table('vehicle_owners')->get();

    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }

  public function store_vehicle(Request $request,$id=null)
  {
    DB::beginTransaction();
    if ($id) {
      $request->validate([
        'nopol' => 'required|unique:vehicles,nopol,'.$id,
        'vehicle_owner_id' => 'required'
      ],[
        'nopol.required' => 'Nomor Polisi harus diisi',
        'vehicle_owner_id.required' => 'Tipe Kepemilikan Kendaraan harus diisi'
      ]);

      Vehicle::find($id)->update([
        'company_id' => auth()->user()->company_id,
        'vehicle_owner_id' => $request->vehicle_owner_id,
        'supplier_id' => auth()->user()->id,
        'code' => $request->code,
        'nopol' => $request->nopol,
        'chassis_no' => $request->chassis_no,
        'machine_no' => $request->machine_no,
        'color' => $request->color,
        'is_active' => $request->is_active,
      ]);
    } else {
      $request->validate([
        'nopol' => 'required|unique:vehicles,nopol',
        'vehicle_owner_id' => 'required'
      ],[
        'nopol.required' => 'Nomor Polisi harus diisi',
        'vehicle_owner_id.required' => 'Tipe Kepemilikan Kendaraan harus diisi'
      ]);

      Vehicle::create([
        'company_id' => auth()->user()->company_id,
        'vehicle_owner_id' => $request->vehicle_owner_id,
        'supplier_id' => auth()->user()->id,
        'code' => $request->code,
        'nopol' => $request->nopol,
        'chassis_no' => $request->chassis_no,
        'machine_no' => $request->machine_no,
        'color' => $request->color,
        'is_active' => $request->is_active,
        'is_internal' => 0
      ]);
    }
    DB::commit();

    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
  }

  public function assign_driver(Request $request,$id)
  {
    $request->validate([
      'driver_id' => 'required',
      'vehicle_id' => 'required'
    ],[
      'driver_id.required' => 'Nama Driver harus dipilih',
      'vehicle_id.required' => 'Kendaraan harus dipilih',
    ]);
    DB::beginTransaction();
    $isi=[
      'vehicle_id' => $request->vehicle_id,
      'driver_id' => $request->driver_id,
    ];
    $dod=DeliveryOrderDriver::find($id);
    Manifest::find($dod->manifest_id)->update($isi);
    $isi['job_status_id']=2;//asign to driver
    //update history status
    JobStatusHistory::create([
      'delivery_id' => $dod->id,
      'job_status_id' => 2,
      'vendor_id' => auth()->id(),
      'driver_id' => $request->driver_id,
      'vehicle_id' => $request->vehicle_id
    ]);
    //end update
    $dod->update($isi);
    DB::commit();

    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
  }

  public function reject_job($id)
  {
    DB::beginTransaction();
    $isi=[
      'vehicle_id' => null,
      'driver_id' => null,
      'nopol' => null,
      'driver' => null,
    ];
    $dod=DeliveryOrderDriver::find($id);
    Manifest::find($dod->manifest_id)->update($isi);
    $js=DB::table('job_statuses')->where('is_reject',1)->first();
    $isi['job_status_id']=$js->id;
    $isi['vendor_id']=null;
    $dod->update($isi);
    DB::commit();

    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
  }

  public function store_driver(Request $request,$id=null)
  {
    DB::beginTransaction();
    if ($id) {
      $request->validate([
        'email' => 'required|unique:contacts,email,'.$id,
        'name' => 'required',
      ],[
        'email.required' => 'Email Driver harus diisi',
        'name.required' => 'Nama driver harus diisi',
      ]);

      $update=[
        'company_id' => auth()->user()->company_id,
        'parent_id' => auth()->user()->id,
        'is_driver' => 1,
        'is_internal' => 0,
        'name' => $request->name,
        'address' => $request->address,
        'email' => $request->email,
        'phone' => $request->phone,
        'is_active' => $request->is_active,
      ];
      if ($request->password) {
        $update['password']=bcrypt($request->password);
      }
      Contact::find($id)->update($update);
    } else {
      $request->validate([
        'email' => 'required|unique:contacts,email',
        'name' => 'required',
        'password' => 'required'
      ],[
        'email.required' => 'Email Driver harus diisi',
        'name.required' => 'Nama driver harus diisi',
        'password.required' => 'Password driver harus diisi'
      ]);

      Contact::create([
        'company_id' => auth()->user()->company_id,
        'parent_id' => auth()->user()->id,
        'is_driver' => 1,
        'is_internal' => 0,
        'name' => $request->name,
        'address' => $request->address,
        'email' => $request->email,
        'phone' => $request->phone,
        'password' => bcrypt($request->password),
        'is_active' => $request->is_active,
        'api_token' => str_random(100)
      ]);
    }
    DB::commit();

    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
  }
  public function delete_vehicle_driver($id)
  {
    DB::beginTransaction();
    VehicleContact::find($id)->delete();
    DB::commit();

    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
  }

  public function delete_vehicle($id)
  {
    DB::beginTransaction();
    Vehicle::find($id)->delete();
    DB::commit();

    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
  }

  public function show_vehicle($id)
  {
    $data['item']=DB::table('vehicles')
    ->leftJoin('vehicle_owners','vehicle_owners.id','vehicles.vehicle_owner_id')
    ->where('vehicles.id', $id)
    ->selectRaw('vehicles.*,vehicle_owners.name as vehicle_owner')->first();
    $data['vehicle_owner']=DB::table('vehicle_owners')->get();

    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }
  public function show_driver($id)
  {
    $data['item']=DB::table('contacts')
    ->where('contacts.id', $id)
    ->selectRaw('contacts.*')->first();

    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }

  public function vehicle_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->supplier_id) {
      $wr.=" and vehicles.supplier_id = ".$request->supplier_id;
    }
    if ($request->driver_id) {
      $wr.=" and vehicle_contacts.contact_id = ".$request->driver_id;
    }

    $item=DB::table('vehicles')
    ->leftJoin('vehicle_owners','vehicle_owners.id','vehicles.vehicle_owner_id')
    ->leftJoin('vehicle_contacts','vehicle_contacts.vehicle_id','vehicles.id')
    ->leftJoin('contacts','vehicle_contacts.contact_id','contacts.id')
    ->whereRaw($wr)
    ->selectRaw('
      vehicles.*,
      vehicle_owners.name as owner,
      group_concat(contacts.name separator "<br>") as driver_list,
      concat(ifnull(vehicles.chassis_no,\'-\'),\' / \',ifnull(vehicles.machine_no,\'-\')) as chassis_machine
    ')
    ->groupBy('vehicles.id');

    return DataTables::of($item)
    ->filterColumn('chassis_machine', function($query, $keyword) {
      $sql="concat(ifnull(vehicles.chassis_no,'-'),' / ',ifnull(vehicles.machine_no,'-')) like ?";
      $query->whereRaw($sql, ["%{$keyword}%"]);
    })
    ->editColumn('action', function($item){
      $html="<a ui-sref=\"p.vehicle.show({id:$item->id})\"><i class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></i></a>&nbsp;";
      $html.="<a ui-sref=\"p.vehicle.edit({id:$item->id})\"><i class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></i></a>&nbsp;";
      $html.="<a href='' ng-click=\"deletes($item->id)\"><i class='fa fa-trash'></i></a>";
      return $html;
    })
    ->editColumn('is_active', function($item){
      $stt=[
        1 => '<span class="badge badge-success">AKTIF</span>',
        2 => '<span class="badge badge-danger">TIDAK AKTIF</span>',
      ];
      return $stt[$item->is_active];
    })
    ->rawColumns(['action','is_active','driver_list'])
    ->make(true);
  }
  public function vehicle_driver_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->driver_id) {
      $wr.=" and vehicle_contacts.contact_id = ".$request->driver_id;
    }

    $item=DB::table('vehicle_contacts')
    ->leftJoin('vehicles','vehicles.id','vehicle_contacts.vehicle_id')
    ->whereRaw($wr)
    ->selectRaw('
      vehicles.*,
      vehicle_contacts.id as vid,
      concat(ifnull(vehicles.chassis_no,\'-\'),\' / \',ifnull(vehicles.machine_no,\'-\')) as chassis_machine
    ');

    return DataTables::of($item)
    ->filterColumn('chassis_machine', function($query, $keyword) {
      $sql="concat(ifnull(vehicles.chassis_no,'-'),' / ',ifnull(vehicles.machine_no,'-')) like ?";
      $query->whereRaw($sql, ["%{$keyword}%"]);
    })
    ->editColumn('action', function($item){
      $html="<a ng-click=\"deletes($item->vid)\"><i class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></i></a>";
      return $html;
    })
    ->editColumn('is_active', function($item){
      $stt=[
        1 => '<span class="badge badge-success">AKTIF</span>',
        2 => '<span class="badge badge-danger">TIDAK AKTIF</span>',
      ];
      return $stt[$item->is_active];
    })
    ->rawColumns(['action','is_active'])
    ->make(true);
  }
  public function driver_datatable(Request $request)
  {
    $wr="contacts.is_driver = 1";
    if (auth()->user()->id) {
      $wr.=" and contacts.parent_id = ".auth()->user()->id;
    }

    $item=DB::table('contacts')
    ->leftJoin('vehicle_contacts','vehicle_contacts.contact_id','contacts.id')
    ->leftJoin('vehicles','vehicle_contacts.vehicle_id','vehicles.id')
    ->whereRaw($wr)
    ->selectRaw('
      contacts.*,
      group_concat(vehicles.nopol) as vehicle_list
    ')->groupBy('contacts.id');

    return DataTables::of($item)
    ->filterColumn('vehicle_list', function($query, $keyword) {
      $sql="group_concat(vehicles.nopol) like ?";
      $query->whereRaw($sql, ["%{$keyword}%"]);
    })
    ->editColumn('action', function($item){
      $html="<a ui-sref=\"p.driver.show({id:$item->id})\"><i class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></i></a>&nbsp;";
      $html.="<a ui-sref=\"p.driver.edit({id:$item->id})\"><i class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></i></a>&nbsp;";
      $html.="<a ng-click=\"deletes($item->id)\"><i class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></i></a>";
      return $html;
    })
    ->editColumn('is_active', function($item){
      $stt=[
        1 => '<span class="badge badge-success">AKTIF</span>',
        0 => '<span class="badge badge-danger">TIDAK AKTIF</span>',
      ];
      return $stt[$item->is_active];
    })
    ->rawColumns(['action','is_active'])
    ->make(true);
  }

  public function vehicle_list(Request $request)
  {
    $item=DB::table('vehicles')->where('supplier_id', $request->supplier_id)->selectRaw('id,nopol')->get();
    return Response::json($item,200,[],JSON_NUMERIC_CHECK);
  }
  public function login(Request $request)
  {
    $auth=Auth::guard('contact')->attempt([
      'email' => $request->email,
      'password' => $request->password,
    ]);

    if ($auth) {
      User::where('email', $request->email)->update([
        'api_token' => str_random(100)
      ]);
      $user=DB::table('contacts')
      ->where('contacts.email', $request->email)
      ->selectRaw('
        contacts.name,
        contacts.email,
        contacts.api_token,
        contacts.is_vendor,
        contacts.id,
        contacts.id as contact_id,
        contacts.name as contact_name,
        contacts.address as contact_address,
        contacts.phone as contact_phone,
        contacts.rek_bank_id as contact_bank_id,
        contacts.rek_cabang as contact_rek_cabang,
        contacts.rek_milik as contact_rek_name,
        contacts.rek_no as contact_rek_no
      ')->first();
      // dd($user);
      if ($user->is_vendor==0) {
        return Response::json(['message' => 'Bukan akun customer!'],500);
      } elseif (empty($user->contact_id)) {
        return Response::json(['message' => 'Akun anda belum terdaftar, silahkan menghubungi admin!'],500);
      }
      return Response::json($user, 200, [], JSON_NUMERIC_CHECK);
    } else {
      return Response::json(['message' => 'Username atau Password tidak cocok!'],500);
    }
  }

  public function store_vehicle_driver(Request $request)
  {
    $request->validate([
      'vehicle_id' => 'required',
      'driver_id' => 'required',
    ],[
      'vehicle_id.required' => 'Kendaraan Harus dipilih!'
    ]);
    DB::beginTransaction();
    VehicleContact::create([
      'contact_id' => $request->driver_id,
      'vehicle_id' => $request->vehicle_id,
      'is_active' => 1,
      'driver_status' => 1
    ]);
    DB::commit();

    return Response::json(null,200,[],JSON_NUMERIC_CHECK);
  }
  public function order_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->contact_id) {
      $wr.=" and delivery_order_drivers.vendor_id = $request->contact_id";
    }
    if ($request->is_onprogress) {
      $getStatus=DB::table('job_statuses')->whereRaw('is_start = 1 or is_finish = 1')->pluck('id')->toArray();
      // dd($getStatus);
      $wr.=" and (delivery_order_drivers.job_status_id between ".$getStatus[0]." and ".$getStatus[1].")";
    }
    if ($request->is_finished) {
      $wr.=" and delivery_order_drivers.is_finish = 1";
    }
    $item=DB::table('delivery_order_drivers')
    ->leftJoin('manifests','manifests.id','delivery_order_drivers.manifest_id')
    ->leftJoin('vehicle_types','vehicle_types.id','manifests.vehicle_type_id')
    ->leftJoin('container_types','container_types.id','manifests.container_type_id')
    ->leftJoin('routes','routes.id','manifests.route_id')
    ->leftJoin('contacts','contacts.id','delivery_order_drivers.driver_id')
    ->leftJoin('vehicles','vehicles.id','delivery_order_drivers.vehicle_id')
    ->leftJoin('job_statuses','job_statuses.id','delivery_order_drivers.job_status_id')
    ->whereRaw($wr)
    ->selectRaw('
    delivery_order_drivers.*,
    routes.name as route,
    job_statuses.name as job_status,
    vehicles.nopol,
    contacts.name as driver,
    if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as vtype
    ');

    return Datatables::of($item)
    ->editColumn('action', function($item){
      $html="<a ui-sref=\"p.offer.show({id:$item->id})\"><i class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></i></a>&nbsp;";
      return $html;
    })
    ->filterColumn('vtype', function($query, $keyword) {
      $sql="if(vehicle_types.id is not null,vehicle_types.name,container_types.code) like ?";
      $query->whereRaw($sql, ["%{$keyword}%"]);
    })
    ->rawColumns(['action'])
    ->make(true);
  }

  public function detail_order(Request $request,$id)
  {
    $data['item']=DB::table('delivery_order_drivers')
    ->leftJoin('manifests','manifests.id','delivery_order_drivers.manifest_id')
    ->leftJoin('vehicle_types','vehicle_types.id','manifests.vehicle_type_id')
    ->leftJoin('container_types','container_types.id','manifests.container_type_id')
    ->leftJoin('routes','routes.id','manifests.route_id')
    ->leftJoin('containers','containers.id','manifests.container_id')
    ->leftJoin('vehicles','vehicles.id','delivery_order_drivers.vehicle_id')
    ->leftJoin('contacts','contacts.id','delivery_order_drivers.driver_id')
    ->leftJoin('contacts as vendor','vendor.id','delivery_order_drivers.vendor_id')
    ->leftJoin('job_statuses','job_statuses.id','delivery_order_drivers.job_status_id')
    ->where('delivery_order_drivers.id',$id)
    ->selectRaw('
    delivery_order_drivers.id,
    delivery_order_drivers.code as surat_jalan,
    delivery_order_drivers.job_status_id,
    manifests.code as no_manifest,
    routes.name as rute,
    manifests.id as manifest_id,
    contacts.name as driver,
    vehicles.nopol as nopol,
    containers.container_no as no_container,
    delivery_order_drivers.is_finish,
    vendor.name as vendor_name,
    if(manifests.is_full=1 and is_container = 1,\'FCL\',if(manifests.is_full=0 and is_container = 1,\'LCL\',if(manifests.is_full=1 and is_container = 0,\'FTL\',\'LTL\'))) as tipe_angkut,
    if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as tipe_kendaraan,
    job_statuses.name as status,
    (select if(is_cancel=0 and is_reject=0,name,null) from job_statuses as jss where urut > job_statuses.urut order by urut asc limit 1) as next_status
    ')->first();
    $data['detail']=DB::table('manifest_details')
    ->leftJoin('job_order_details','job_order_details.id','manifest_details.job_order_detail_id')
    ->leftJoin('job_orders','job_orders.id','job_order_details.header_id')
    ->leftJoin('contacts as to','to.id','job_orders.receiver_id')
    ->leftJoin('commodities','commodities.id','job_order_details.commodity_id')
    ->leftJoin('quotation_details','quotation_details.id','job_order_details.quotation_detail_id')
    ->leftJoin('pieces','pieces.id','job_order_details.piece_id')
    ->where('manifest_details.header_id', $data['item']->manifest_id)
    ->selectRaw('
      job_order_details.item_name,
      commodities.name as komoditas,
      manifest_details.transported as qty,
      pieces.name as satuan,
      job_order_details.weight,
      job_order_details.long,
      job_order_details.high,
      to.name as nama_tujuan,
      to.address as alamat_tujuan
    ')
    ->get();
    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }

  public function order($id)
  {
    $data['item']=DB::table('delivery_order_drivers')
    ->leftJoin('manifests','manifests.id','delivery_order_drivers.manifest_id')
    ->leftJoin('vehicle_types','vehicle_types.id','manifests.vehicle_type_id')
    ->leftJoin('container_types','container_types.id','manifests.container_type_id')
    ->leftJoin('routes','routes.id','manifests.route_id')
    ->whereRaw('delivery_order_drivers.id', $id)
    ->selectRaw('
    delivery_order_drivers.*,
    manifests.id as id_manifest,
    manifests.code as code_manifest,
    manifests.date_manifest as date_manifest,
    routes.name as route,
    if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as vtype
    ')->first();
    $data['detail']=DB::table('manifest_details')
    ->leftJoin('job_order_details','job_order_details.id','manifest_details.job_order_detail_id')
    ->leftJoin('commodities','commodities.id','job_order_details.commodity_id')
    ->selectRaw('
      commodities.name as komoditas,
      manifest_details.transported,
      job_order_details.item_name
    ')->get();
    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }

  public function get_drivers(Request $request)
  {
    $data['driver']=DB::table('contacts')->whereRaw("is_driver = 1 and parent_id = ".auth()->id())->selectRaw('id,name')->get();
    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }
  public function get_vehicle_drivers(Request $request)
  {
    $data['vehicles']=DB::table('vehicle_contacts')
    ->leftJoin('vehicles','vehicles.id','vehicle_contacts.vehicle_id')
    ->whereRaw('vehicle_contacts.contact_id', $request->driver_id)
    ->selectRaw('vehicles.id,nopol')->get();
    return Response::json($data,200,[],JSON_NUMERIC_CHECK);
  }

}
