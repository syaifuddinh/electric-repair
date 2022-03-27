<?php

namespace App\Http\Controllers\Api\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Manifest;
use App\Model\ManifestDetail;
use App\Model\ManifestCost;
use App\Model\CostType;
use App\Model\Contact;
use App\Model\Company;
use App\Model\VehicleContact;
use App\Model\VehicleType;
use App\Model\JobOrder;
use App\Model\JobOrderDetail;
use App\Model\DeliveryOrderDriver;
use App\Model\SubmissionCost;
use App\Model\Vehicle;
use App\Model\Route as Trayek;
use App\Utils\TransactionCode;
use DB;
use Response;
use Carbon\Carbon;
use PDF;

class ManifestFTLController extends Controller
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
      $data['company']=companyAdmin(auth()->id());
      $data['route']=Trayek::select('id','name')->get();
      $data['vehicle_type']=VehicleType::all();
      $data['customer']=Contact::where('is_pelanggan', 1)->select('id','name')->get();
      $data['vehicle']=Vehicle::select('id','nopol')->get();
      $data['driver']=Contact::where('is_driver', 1)->select('id','name','address')->get();
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
        'date_manifest' => 'required',
        'route_id' => 'required',
        'vehicle_type_id' => 'required',
        'driver_id' => 'required',
        'vehicle_id' => 'required',
        'from_id' => 'required',
        'to_id' => 'required',
      ]);
      DB::beginTransaction();
      $code = new TransactionCode($request->company_id, 'manifest');
      $code->setCode();
      $trx_code = $code->getCode();

      $i=Manifest::create([
        'company_id' => $request->company_id,
        'vehicle_type_id' => $request->vehicle_type_id,
        'route_id' => $request->route_id,
        'transaction_type_id' => 22,
        'vehicle_id' => $request->vehicle_id,
        'reff_no' => $request->reff_no,
        'code' => $trx_code,
        'driver_id' => $request->driver_id,
        'create_by' => auth()->id(),
        'date_manifest' => Carbon::parse($request->date_manifest),
        'is_container' => 0,
        'is_full' => $request->is_full,
        'description' => $request->description,
        'etd_time' => createTimestamp($request->etd_date,$request->etd_time),
        'eta_time' => createTimestamp($request->eta_date,$request->eta_time),
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
      $data['item']=Manifest::with('vehicle_type','delivery','container','container_type','company','trayek')->where('id', $id)->first();
      $data['detail']=ManifestDetail::with('job_order_detail.job_order.customer','job_order_detail.job_order.receiver')->where('header_id', $id)->get();
      $data['cost']=ManifestCost::with('cost_type','vendor')->where('header_id', $id)->get();
      $data['cost_type']=CostType::with('parent')->where('is_invoice', 0)->where('company_id',$data['item']->company_id)->where('parent_id','!=',null)->get();
      $data['vendor']=Contact::whereRaw("is_vendor = 1 and vendor_status_approve = 2")->select('id','name')->get();
      $data['vehicles']=DB::table('vehicles')->selectRaw('id,nopol')->get();
      $data['drivers']=DB::table('contacts')->where('is_driver',1)->selectRaw('id,name')->get();
      $data['delivery_order']=DB::table('delivery_order_drivers')->where('manifest_id', $id)->selectRaw('id,code')->get();
      // dd($data);
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
        //
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
        //
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
      Manifest::find($id)->delete();
      DB::commit();

      return Response::json(null);
    }

    public function add_cost(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'cost_type' => 'required',
        'is_internal' => 'required',
        'vendor_id' => 'required_if:is_internal,0',
        'qty' => 'required|integer|min:1',
        'total_price' => 'required|integer|min:1',
      ]);
      DB::beginTransaction();
      $jo=Manifest::find($id);
      ManifestCost::create([
        'header_id' => $id,
        'company_id' => $jo->company_id,
        'cost_type_id' => $request->cost_type['id'],
        'vendor_id' => $request->vendor_id,
        'qty' => $request->qty,
        'price' => ($request->cost_type['is_bbm']==1?$request->price:$request->total_price),
        'total_price' => $request->total_price,
        'description' => $request->description,
        'is_internal' => $request->is_internal,
        'is_generated' => 1,
        'create_by' => auth()->id(),
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function list_job_order($id)
    {
      $m=Manifest::find($id);
      if ($m->is_full==1) {
        $md=ManifestDetail::where('header_id', $id)->count();
        $wr="";
        if ($md>0) {
          // $wr.="AND job_order_details.id IN ( SELECT job_order_detail_id FROM manifest_details WHERE header_id = $id )";
          $wr.="AND job_order_details.header_id IN ( SELECT * FROM ( SELECT job_order_details.header_id FROM manifest_details LEFT JOIN job_order_details ON job_order_details.id = manifest_details.job_order_detail_id WHERE manifest_details.header_id = $id ) as XX )";
        }
        $sql="
        SELECT
        	job_order_details.id,
        	job_orders.code,
        	contacts.name as customer,
        	job_order_details.item_name,
        	job_order_details.qty,
        	job_order_details.transported,
        	job_order_details.leftover
        FROM
        	job_order_details
        	LEFT JOIN manifest_details ON manifest_details.job_order_detail_id = job_order_details.id
        	LEFT JOIN job_orders ON job_order_details.header_id = job_orders.id
        	LEFT JOIN contacts ON job_orders.customer_id = contacts.id
        	LEFT JOIN manifests ON manifest_details.header_id = manifests.id
        WHERE
          1=1
        	$wr
          AND job_order_details.leftover > 0
        GROUP BY job_order_details.id
        ";
        $data=DB::select($sql);
      } else {
        $sql="
        SELECT
        	job_order_details.id,
        	job_orders.code,
        	contacts.NAME AS customer,
        	job_order_details.item_name,
        	job_order_details.qty,
        	job_order_details.transported,
        	job_order_details.leftover
        FROM
        	job_order_details
        	LEFT JOIN manifest_details ON manifest_details.job_order_detail_id = job_order_details.id
        	LEFT JOIN job_orders ON job_order_details.header_id = job_orders.id
        	LEFT JOIN contacts ON job_orders.customer_id = contacts.id
        	LEFT JOIN manifests ON manifest_details.header_id = manifests.id
        WHERE
          job_order_details.leftover > 0
        GROUP BY job_order_details.id
        ";
        $data=DB::select($sql);
      }
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function create_delivery($id)
    {
      $data['item']=Manifest::find($id);
      $data['customer']=Contact::whereRaw("is_pelanggan = 1")->select('id','name','address')->get();
      $data['vendor']=DB::table('contacts')->where('is_vendor', 1)->selectRaw('id,name')->get();
      $data['driver']=DB::table('contacts')->whereRaw("is_driver = 1")->select('id','name','address','parent_id','is_internal')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function add_item(Request $request, $id)
    {
      // dd($request);
      $request->validate([
        'detail' => 'required'
      ]);
      DB::beginTransaction();
      $m=Manifest::find($id);
      foreach ($request->detail as $key => $value) {
        if ($value['pickup']<1) {
          continue;
        }
        ManifestDetail::create([
          'header_id' => $id,
          'job_order_detail_id' => $value['id'],
          'transported' => $value['pickup'],
          'create_by' => auth()->id(),
          'update_by' => auth()->id(),
        ]);

        JobOrderDetail::find($value['id'])->update([
          'transported' => DB::raw('transported+'.$value['pickup']),
          'leftover' => DB::raw('leftover-'.$value['pickup']),
        ]);
      }
      DB::commit();

      return Response::json(null);
    }

    public function cari_kendaraan($id)
    {
      $data=VehicleContact::with('vehicle')->where('contact_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function store_delivery(Request $request, $id)
    {
      // dd($request);
      $validates=[
        'is_internal_driver' => 'required',
        'vendor_id' => 'required_if:is_internal_driver,0',
        'driver_id' => 'required_if:is_internal_driver,1',
        'pick_date' => 'required',
        'finish_date' => 'required',
        'pick_time' => 'required',
        'finish_time' => 'required',
      ];
      if ($request->driver_id) {
        $validate['vehicle_id']='required';
      }
      $request->validate($validates,[
        'vendor_id.required_if' => 'Vendor harus dimasukkan jika memilih eksternal',
        'driver_id.required_if' => 'Driver harus dimasukkan jika memilih Internal',
        'vehicle_id.required_without' => 'Kendaraan harus dimasukkan jika driver dipilih'
      ]);
      DB::beginTransaction();
      $m=Manifest::find($id);
      $code = new TransactionCode($m->company_id, 'deliveryOrderDriver');
      $code->setCode();
      $trx_code = $code->getCode();

      $unit_create = [
        'manifest_id' => $id,
        'vehicle_id' => $request->vehicle_id,
        'driver_id' => $request->driver_id,
        // 'from_id' => $request->pick_id,
        // 'from_address_id' => $request->pick_address_id,
        // 'to_id' => $request->end_id,
        // 'to_address_id' => $request->end_address_id,
        // 'commodity_name' => $request->commodity_name,
        'pick_date' => createTimestamp($request->pick_date,$request->pick_time),
        'finish_date' => createTimestamp($request->finish_date,$request->finish_time),
        'code' => $trx_code,
        'create_by' => auth()->id(),
      ];
      if($request->is_internal_driver != 1){
        $unit_create['vendor_id'] = $request->vendor_id;
      } else {
        $unit_create['job_status_id']=2;
      }
      if ($request->driver_id) {
        $unit_create['driver_id'] = $request->driver_id;
        $unit_create['vehicle_id'] = $request->vehicle_id;
      }
      DeliveryOrderDriver::create($unit_create);

      $unit_update = [
        'is_internal_driver' => $request->is_internal_driver,
        'vehicle_id' => $request->vehicle_id,
        'driver_id' => $request->driver_id
      ];

      $m->update($unit_update);
      DB::commit();

      return Response::json(null);
    }

    public function store_submission($id)
    {
      $mc=ManifestCost::find($id);
      // $h=Manifest::find($mc->header_id);
      DB::beginTransaction();
      SubmissionCost::create([
        'company_id' => $mc->company_id,
        'relation_cost_id' => $mc->id,
        'type_transaction_id' => 22, //mnaifest
        'vendor_id' => $mc->vendor_id,
        'create_by' => auth()->id(),
        'date_submission' => Carbon::now(),
        'description' => $mc->cost_type->code.' - '.$mc->cost_type->name,
        'type_submission' => 2,
      ]);
      $mc->update([
        'status' => 2
      ]);
      DB::commit();

      return Response::json(null);
    }

    public function delete_detail($id)
    {
      DB::beginTransaction();
      $m=ManifestDetail::find($id);
      JobOrderDetail::where('id', $m->job_order_detail_id)->update([
        'transported' => DB::raw('transported-'.$m->transported),
        'leftover' => DB::raw('leftover+'.$m->transported),
      ]);
      $m->delete();
      DB::commit();
    }

    public function change_depart_arrive(Request $request, $id)
    {
      DB::beginTransaction();
      if (isset($request->depart_date) && isset($request->depart_time)) {
        $depart=createTimestamp($request->depart_date,$request->depart_time);
      }
      if (isset($request->arrive_date) && isset($request->arrive_time)) {
        $arrive=createTimestamp($request->arrive_date,$request->arrive_time);
      }

      Manifest::find($id)->update([
        'depart' => $depart??null,
        'arrive' => $arrive??null,
      ]);
      DB::commit();
      return Response::json(null);
    }

    public function print_sj($id)
    {
      $data['item']=Manifest::find($id);
      $data['detail']=ManifestDetail::where('header_id', $id)->get();
      $data['detail_one']=ManifestDetail::where('header_id', $id)->first();
      return PDF::loadView('pdf.sj_driver', $data)->stream('SJ Driver.pdf');
    }
}
