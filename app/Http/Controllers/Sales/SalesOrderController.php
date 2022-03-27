<?php

namespace App\Http\Controllers\Sales;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\JobOrder AS JO;
use App\Abstracts\Sales\SalesOrder;
use App\Abstracts\Sales\SalesOrderDetail;
use Response;
use Carbon\Carbon;
use Exception;
use App\Utils\TransactionCode;
use DataTables;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
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

    public function create()
    {
    }

    public function detailDatatable(Request $request) {
        $items = SalesOrderDetail::query($request->all());
        $dt = DB::query()->fromSub($items, 'job_order_details');
        $dt = $dt->where('leftover', '>', 0);

        return DataTables::of($dt)
              ->addColumn('qty_selisih',function($item){
                return $item->leftover;
              })
              ->make(true);
    }

    /**
     * Date : 22-07-2021
     * Description  : Menyimpan Sales Order
     * Developer  : Hendra
     * Status   : Create
     */
    public function store(Request $request)
    {
        $data['message'] = 'Data successfully saved';
        $status_code = 200;
        DB::beginTransaction();
        try {
            $sales_service = \App\Http\Controllers\Setting\SettingController::fetch('sales_order', 'sales_service_id');
            $sales_service_id = $sales_service->value;
            if(!$sales_service_id) {
                throw new Exception('Sales service need to set-up, please open setting');
            }

            $service = DB::table('services')
            ->whereId($sales_service_id)
            ->first();

            if(!$service) {
                throw new Exception('Service not found, please open setting');
            }
            $request->company_id = auth()->user()->company_id;
            $request->no_po_customer = $request->wo_customer;
            $request->type_tarif = 2;
            $request->service_id = $service->id;

            if(is_array($request->detail)) {
                $detail = $request->detail;
                foreach($detail as $i => $d) {
                    if(empty($d)) {
                        continue;
                    }
                    if(is_array($d)){
                        $detail[$i]['imposition'] = 3;
                    } else {
                        $detail[$i]->imposition = 3;
                    }
                }
                $request->detail = $detail;
            }

            $job_order_id = JO::store($request);
            $latestJobOrder = DB::table('job_orders')
            ->whereId($job_order_id)
            ->first();

            $code = new TransactionCode($latestJobOrder->company_id, 'salesOrder');
            $code->setCode();
            $trx_code = $code->getCode();
            $params = [];
            $params['job_order_id'] = $job_order_id;
            $params['customer_order_id'] = $request->customer_order_id ?? null;
            $params['code'] = $trx_code;
            $params['sales_order_status_id'] = 0; // sementara

            $sales_order_id = DB::table('sales_orders')
            ->insertGetId($params);

            $updatePrice = SalesOrder::countPrice($sales_order_id);
            $getLimit = SalesOrder::validasiLimitPiutang($sales_order_id);

            if($getLimit == true && $request->payment == 2){
                $statusSO = DB::table('sales_order_statuses')->where('slug', 'waiting_for_approval')->first();
            } else if($request->payment == 1){
                $statusSO = DB::table('sales_order_statuses')->where('slug', 'waiting_for_payment')->first();
            } else {
                $statusSO = DB::table('sales_order_statuses')->where('slug', 'approved')->first();
            }

            if(empty($statusSO)){
                throw new Exception('Status Sales Order tidak ditemukan');
            }

            $updateStatusSO = DB::table('sales_orders')
                                ->where('id', $sales_order_id)
                                ->update(['sales_order_status_id' => $statusSO->id]);

            $data['data'] = ['id' => $sales_order_id];
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            if($request->customer_order_id){
                throw new Exception('Failed generate Sales Order. ' . (env('APP_DEBUG', false) ? $e->getMessage() : ''), 421);
            }
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }


        return Response::json($data, $status_code);
    }

    /*
      Date : 19-04-2021
      Description : Menampilkan data
      Developer : Didin
      Status : Create
    */
    public function show($id)
    {
        $data['message'] = 'OK';
        $status_code = 200;
        try {
            $dt = SalesOrder::show($id);
            if(!$dt) {
                throw new Exception('Data not found');
            }
            $data['data'] = $dt;
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return Response::json($data, $status_code);
    }

    /*
      Date : 19-04-2021
      Description : Menampilkan data
      Developer : Didin
      Status : Create
    */
    public function showDetail($id)
    {
        $data['message'] = 'OK';
        $status_code = 200;
        try {
            $dt = SalesOrderDetail::index($id);
            if(!$dt) {
                throw new Exception('Data not found');
            }
            $data['data'] = $dt;
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return Response::json($data, $status_code);
    }
    /*
      Date : 19-04-2021
      Description : Menampilkan data
      Developer : Didin
      Status : Create
    */
    public function showDetailInfo($id, $sales_order_detail_id)
    {
        $data['message'] = 'OK';
        $status_code = 200;
        try {
            JO::validate($id);
            $dt = SalesOrderDetail::show($sales_order_detail_id);
            $data['data'] = $dt;
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return Response::json($data, $status_code);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
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
      // Contact::find($id)->update([
      //   'address' => $request->address,
      //   'akun_hutang' => $request->akun_hutang,
      //   'akun_piutang' => $request->akun_piutang,
      //   'akun_um_customer' => $request->akun_um_customer,
      //   'akun_um_supplier' => $request->akun_um_supplier,
      //   'city_id' => $request->city_id,
      //   'company_id' => $request->company_id,
      //   'contact_person' => $request->contact_person,
      //   'contact_person_email' => $request->contact_person_email,
      //   'contact_person_no' => $request->contact_person_no,
      //   'description' => $request->description,
      //   'email' => $request->email,
      //   'fax' => $request->fax,
      //   'is_vendor' => $request->is_vendor,
      //   'limit_hutang' => $request->limit_hutang,
      //   'limit_piutang' => $request->limit_piutang,
      //   'name' => $request->name,
      //   'npwp' => $request->npwp,
      //   'phone' => $request->phone,
      //   'phone2' => $request->phone2,
      //   'pkp' => $request->pkp,
      //   'postal_code' => $request->postal_code,
      //   'rek_bank_id' => $request->rek_bank_id,
      //   'rek_cabang' => $request->rek_cabang,
      //   'rek_milik' => $request->rek_milik,
      //   'rek_no' => $request->rek_no,
      //   'term_of_payment' => $request->term_of_payment,
      //   'vendor_type_id' => $request->vendor_type_id,
      //   'vendor_register_date' => Carbon::now(),
      //   'vendor_status_approve' => 1,
      // ]);
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
        $data['message'] = 'Data successfully deleted';
        $status_code = 200;
        DB::beginTransaction();
        try {
            SalesOrder::destroy($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            // dd($e);
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return Response::json($data, $status_code);
    }

    public function document($id)
    {
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

    /**
     * Date : 22-07-2021
     * Description : Approve Sales Order meskipun melebihi piutang customer 
     * Developer : Hendra
     * Status : Create
     */
    public function approve(Request $request, $id)
    {
      if(!auth()->user()->hasRole('sales.sales_order.approve')){
          throw new Exception('Anda tidak memiliki hak akses ini');
      }
      $data['message'] = 'Sales Order Approved';
      $status_code = 200;

      SalesOrder::approve($id);

      return response()->json($data, $status_code);
    }
    
    /**
     * Date : 22-07-2021
     * Description : Reject Sales Order melebihi piutang customer 
     * Developer : Hendra
     * Status : Create
     */
    public function reject(Request $request, $id)
    {
      if(!auth()->user()->hasRole('sales.sales_order.reject')){
        throw new Exception('Anda tidak memiliki hak akses ini');
      }
      $data['message'] = 'Sales Order Rejected';
      $status_code = 200;

      SalesOrder::reject($id);

      return response()->json($data, $status_code);
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
