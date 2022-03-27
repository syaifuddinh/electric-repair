<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\Model\CustomerPrice;
use App\Model\Quotation;
use App\Model\QuotationDetail;
use App\Model\QuotationCost;
use App\Model\JobOrder;
use App\Model\JobOrderDetail;
use App\Model\Invoice;
use App\Model\InvoiceDetail;
use App\Model\InvoiceTax;
use App\Model\InqueryCustomer;
use App\Model\WorkOrder;
use App\Model\WorkOrderDetail;
use Response;
use DB;
use Auth;
use DataTables;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Marketing\Quotation AS Q;
use App\Abstracts\Marketing\QuotationDetail AS QD;

class CustomerController extends Controller
{

  public function login(Request $request)
  {
    // dd($request);
    $auth=Auth::attempt([
      'email' => $request->email,
      'password' => $request->password,
    ]);

    if ($auth) {
      User::where('email', $request->email)->update([
        'api_token' => str_random(100)
      ]);
      $user=User::with('contact')->where('email', $request->email)->first();
      if ($user->is_customer==0) {
        return Response::json(['message' => 'Bukan akun customer!'],500);
      }
      return Response::json($user, 200, [], JSON_NUMERIC_CHECK);
    } else {
      return Response::json(['message' => 'Username atau Password tidak cocok!'],500);
    }
  }

  // public function login(Request $request)
  // {
  //   // dd($request);
  //   $auth=Auth::attempt([
  //     'email' => $request->email,
  //     'password' => $request->password,
  //   ]);

  //   if ($auth) {
  //     $due_date = User::where('email', $request->email)->first()->due_date;
  //     $due_date = $due_date != null ? $due_date : Carbon::now();
  //     User::where('email', $request->email)->update([
  //       'api_token' => str_random(100),
  //       'due_date' => DB::raw("(SELECT DATE_ADD('$due_date', INTERVAL 30 DAY))")
  //     ]);
  //     $user=User::where('email', $request->email)->first();
  //     return Response::json($user, 200, [], JSON_NUMERIC_CHECK);
  //   } else {
  //     return Response::json(['message' => 'Username atau Password tidak cocok!'],500);
  //   }

  // }
  public function cek_token(Request $request)
  {
    // dd($request);
    $auth = $request->header('Authorization');

    // AuthList[0] is token type
    // AuthList[1] is api token
    $auth_list = explode(' ', $auth);

    if($auth_list[0] != 'Bearer') {
        return Response::json(['message' => 'Invalid token type!'],500);
    }

    $users = User::where('api_token', $auth_list[1])->first();
    if($users != null) {
      $due_date = $users->due_date;
      $interval = User::where('api_token', $request->api_token)->selectRaw("DATEDIFF('" . $due_date . "', NOW()) AS `interval`")->first()->interval;
      if($interval <= 0) {
        return Response::json(['message' => 'Token sudah expired!'],500);
      }
      else {
        return Response::json(['message' => 'Token masih berlaku', 'user' => $users],200);

      }
    }
    else {
      return Response::json(['message' => 'Token sudah expired!'],500);

    }

  }

  public function logout_customer_channel(Request $request)
  {
    DB::beginTransaction();
    User::find(auth()->id())->update([
      'api_token' => null
    ]);
    DB::commit();
  }

  public function logout(Request $request)
  {
    $auth = $request->header('Authorization');
    // AuthList[0] is token type
    // AuthList[1] is api token
    $auth_list = explode(' ', $auth);


    if($auth_list[0] != 'Bearer') {
        return Response::json(['message' => 'Invalid token type!'],500);
    }

    $users = User::where('api_token', $auth_list[1]);

    if($users->first() == null ){
      return Response::json(['message' => 'Token sudah expired!'],500);
    }
    else {
      $users->update([
        'api_token' => str_random(100)
      ]);
    }

    return Response::json(['message' => 'Logout berhasil'],200);


  }

  public function get_user(Request $request)
  {
    $data=User::with('contact')->where('id', auth()->id())->first();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function get_contact(Request $request)
  {
    $data['item']=User::with('contact')->where('id', auth()->id())->first();
    $data['address']=DB::table('contact_addresses')
    ->leftJoin('contacts','contacts.id','=','contact_addresses.contact_address_id')
    ->leftJoin('contacts as tagih','tagih.id','=','contact_addresses.contact_bill_id')
    ->leftJoin('cities','cities.id','=','contacts.city_id')
    ->where('contact_addresses.contact_id', $data['item']->contact_id)
    ->select('contacts.*','tagih.name as tertagih','cities.name as city')
    ->get();
    $data['last_shipment']=DB::table('job_orders')->where('customer_id', $data['item']->contact_id)->orderBy('shipment_date','desc')->select('shipment_date')->first();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }


  public function customer_price_datatable(Request $request)
  {
    $wr="1=1";
    if (isset($request->customer_id)) {
      $wr.=" AND customer_id = " . $request->customer_id;
    }

    if (isset($request->service_type_id)) {
        if($request->service_type_id != -1)
            $wr.=" AND customer_prices.service_type_id = $request->service_type_id AND customer_prices.combined_price_id IS NULL";
        else
            $wr .= " AND customer_prices.combined_price_id IS NOT NULL";
    }

    $item = CustomerPrice::with('customer','company','commodity','service','piece','route','moda','vehicle_type','service_type')->whereRaw($wr)->selectRaw('customer_prices.*')->orderBy('created_at', 'DESC')->get();
    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('')\" ui-sref=\"vendor.register_vendor.show.price.edit({id:$item->customer_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->addColumn('action_marketing', function($item){
        $html="<a ng-show=\"roleList.includes('marketing.customer_price.detail')\" ui-sref=\"marketing.customer_price.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('marketing.customer_price.edit')\" ui-sref=\"marketing.customer_price.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('marketing.customer_price.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->addColumn('action_fr_contact', function($item){
        $html="<a ng-show=\"roleList.includes('')\" ui-sref=\"contact.vendor.show.price.edit({id:$item->customer_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->addColumn('action_approve', function($item){
        $html="<a ng-show=\"roleList.includes('')\" ui-sref=\"vendor.vendor.show.price.edit({id:$item->vendor_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->rawColumns(['action','action_approve','action_fr_contact','action_marketing'])
      ->make(true);
  }

    public function show_contract($id)
    {
        $data=Quotation::where('id',$id)->with('company','customer','sales')->first();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public static function show_quotation_file($id) {
        $dt = QF::index($id);

        return $dt;
    }

  public function show_item_contract($id)
  {
    $data['details']=QuotationDetail::with('commodity','service','route','service','vehicle_type')->where('header_id', $id)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function show_job_order($id)
  {
    $data['item']=JobOrder::with('trayek','moda','collectible','container_type','vehicle_type','customer','service','kpi_status','sender','receiver','service.service_type','work_order')->where('id', $id)->first();
    $sql="
    SELECT
      manifests.id,
      manifests.code,
      contacts.name as driver,
      vehicles.nopol,
      vehicle_types.name as vname,
      CONCAT(container_types.code,' - ',container_types.name) as cname,
      IF(manifests.status=1,'Packing List',IF(manifests.status=2,'Berangkat',IF(manifests.status=3,'Sampai','Selesai'))) as status_name,
      voyage_schedules.eta,
      voyage_schedules.etd,
      containers.stuffing,
      containers.stripping,
      manifests.depart,
      manifests.arrive,
      manifests.container_id,
      CONCAT(IFNULL(vessels.name,''),' - ',IFNULL(voyage_schedules.voyage,'')) as voyage,
      CONCAT(IFNULL(container_types.code,''),' - ',IFNULL(containers.container_no,'')) as container
    FROM
      manifests
      LEFT JOIN manifest_details ON manifest_details.header_id = manifests.id
      LEFT JOIN vehicles ON manifests.vehicle_id = vehicles.id
      LEFT JOIN contacts ON contacts.id = manifests.driver_id
      LEFT JOIN vehicle_types ON vehicle_types.id = manifests.vehicle_type_id
      LEFT JOIN container_types ON container_types.id = manifests.container_type_id
      LEFT JOIN containers ON containers.id = manifests.container_id
      LEFT JOIN voyage_schedules ON voyage_schedules.id = containers.voyage_schedule_id
      LEFT JOIN vessels ON vessels.id = voyage_schedules.vessel_id
    WHERE
      manifest_details.job_order_detail_id IN ( SELECT id FROM job_order_details WHERE header_id = $id )
    GROUP BY
      manifests.id
    ";
    $data['manifest']=DB::select($sql);
    $data['detail']=JobOrderDetail::with('piece')->where('header_id', $id)->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function change_password(Request $request)
  {
    DB::beginTransaction();
    $cek=DB::table('users')->where('id', auth()->id())->where('pass_text', $request->old_password)->count();
    if ($cek<1) {
      return Response::json(['message' => 'password lama anda salah!'],500);
    }
    User::find(auth()->id())->update([
      'password' => bcrypt($request->password),
      'pass_text' => $request->password
    ]);
    DB::commit();
  }

  public function show_invoice($id)
  {
    $data['item']=Invoice::with('company','customer')->where('id', $id)->first();
    $data['detail1']=InvoiceDetail::with('job_order','manifest.container','manifest.vehicle','job_order.commodity','job_order.service','cost_type')->where('header_id', $id)->get();
    $data['taxes']=InvoiceTax::where('header_id', $id)->sum('amount');
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function show_quotation($id)
  {
    $data['item'] = Q::show($id);
    $data['details']=QD::index($id);
    $data['cost']=QuotationCost::with('quotation_detail.service','vendor','cost_type')->where('header_id', $id)->orderBy('quotation_detail_id','asc')->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function job_order_document(Request $request,$id)
  {
    $dt=DB::table('job_order_documents')->where('is_customer_view',1)->where('header_id', $id)->get();
    $data=[];
    foreach ($dt as $value) {
      $data[]=[
        'file_path' => asset($value->file_name),
        'file_name' => $value->name,
        'file_extension' => $value->extension,
        'file_date' => $value->created_at
      ];
    }
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }
  public function quotation_document(Request $request,$id)
  {
    $dt=DB::table('quotation_files')->where('is_customer_view',1)->where('header_id', $id)->get();
    $data=[];
    foreach ($dt as $value) {
      $data[]=[
        'file_path' => asset($value->file_name),
        'file_name' => $value->name,
        'file_extension' => $value->extension,
        'file_date' => $value->created_at
      ];
    }
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

    public function store_inquery(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'description' => 'required'
        ]);

        DB::beginTransaction();
        $file = null;
        if ($request->file) {
            $file=$request->file('file');
            $ext = $file->getClientOriginalExtension();
            if($ext != 'png' && $ext != 'jpg' && $ext != 'jpeg' && $ext != 'pdf') {
                throw new Exception('File yang diizinkan hanya yang mempunyai ekstensi .png, .jpg, .jpeg, atau .pdf');
            }
            $filename='INQ_CUSTOMER_'.date('Ymd_His').'_'.str_random(6).'.'.$file->getClientOriginalExtension();
        } 
        InqueryCustomer::create([
          'customer_id' => auth()->user()->contact_id,
          'name' => $request->name,
          'description' => $request->description,
          'file_name' => ($file?("files/$filename"):null),
          'file_extension' => ($file?$file->getClientOriginalExtension():null)
        ]);
        if ($file) {
            $file->move(public_path('files'), $filename);
        }
        DB::commit();

        return Response::json(null);
    }

  public function show_inquery($id)
  {
    $data['item']=InqueryCustomer::with('customer')->where('id', $id)->first();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function show_work_order($id)
  {
    try {
    $data['item'] = WorkOrder::with('quotation','customer.company','company')
      ->where('id', $id)
      ->first();

    $data['detail_jo'] = JobOrder::with('service_type','kpi_status','service')
      ->where('work_order_id', $id)
      ->get();

    $data['cost_detail'] = DB::table('view_work_order_costs')
      ->where('id', $id)
      ->first();

    $data['detail'] = WorkOrderDetail::with(
        'quotation_detail.service',
        'quotation_detail.route',
        'quotation_detail.commodity',
        'quotation_detail.vehicle_type',
        'quotation_detail.container_type',
        'quotation_detail.piece',
        'price_list.service',
        'price_list.route',
        'price_list.commodity',
        'price_list.vehicle_type',
        'price_list.container_type',
        'price_list.piece',
        'customer_price.service',
        'customer_price.route',
        'customer_price.commodity',
        'customer_price.vehicle_type',
        'customer_price.container_type',
        'customer_price.piece')
      ->where('header_id', $id)
      ->leftJoin(DB::raw("(select work_order_detail_id,count(id) as total_jo from job_orders group by work_order_detail_id) Y"),"work_order_details.id","=","Y.work_order_detail_id")
      ->get();
    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    } catch(Exception $e){
    return Response::json($e, 200, [], JSON_NUMERIC_CHECK);
    }
  }
}
