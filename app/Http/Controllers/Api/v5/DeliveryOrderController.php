<?php

namespace App\Http\Controllers\Api\v5;

use App\Abstracts\Contact\ContactLocation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Operational\V2\DeliveryOrderDriver;
use App\Abstracts\Operational\DeliveryOrderDriverDocument;
use App\Abstracts\Operational\Manifest;
use App\Abstracts\Operational\ManifestDetail;
use App\Abstracts\Setting\JobStatus;
use App\Abstracts\Vehicle\VehicleDriver;
use App\Abstracts\JobOrder;
use Exception;
use Illuminate\Support\Facades\DB;

class DeliveryOrderController extends Controller
{

    /*
      Date : 09-07-2021
      Description : Login 
      Developer : Didin
      Status : Create
    */
    public function store(Request $request)
    {
        $resp = [];
        $resp['message'] = 'OK';
        $statusCode = 200;
        DB::beginTransaction();
        try {
            $params = $request->all();
            $params['contact_id'] = auth()->user()->contact_id;
            ContactLocation::store($params);
            DB::commit();
        } catch(\Exception $e) {
            DB::rollback();
            $statusCode = 421;
            $resp['message'] = $e->getMessage();
        }

        return response()->json($resp, $statusCode);
    }

    public static function index(Request $request) {
        $params = [];
        $user = auth()->user();
        $params['driver_id'] = $user->contact_id;
        $vehicle = VehicleDriver::showVehicleByDriver($user->contact_id);
        if($vehicle) {
            $params['vehicle_id'] = $vehicle->id;;
        }
        $dt = DeliveryOrderDriver::query($params);
        $dt = $dt->select(
            'delivery_order_drivers.id',
            'delivery_order_drivers.pick_date',
            'delivery_order_drivers.code',
            DB::raw('"internal" AS shipping_by'),
            'delivery_order_drivers.created_at',
            'routes.name AS route',
            'vehicles.nopol AS police_no',
            'driver.name AS driver',
            'delivery_order_drivers.job_status_id AS status_id',
            'job_statuses.name AS status_name'
        );

        $r = $dt->paginate(10);

        return response()->json($r);
    }

    public static function show($id) {
        $resp = [];
        $resp['message'] = 'OK';
        $data = DeliveryOrderDriver::show($id);

        $resp['data'] = $data;

        return response()->json($resp);
    }

    public static function indexManifest($id) {
        DeliveryOrderDriver::validate($id);
        $resp = [];
        $resp['message'] = 'OK';
        $deliveryOrder = ManifestDetail::query([
            'delivery_order_id' => $id
        ]);


        $deliveryOrder = $deliveryOrder->select(
            "manifest_details.id AS manifest_detail_id",
            "job_orders.code AS code",
            "manifests.id AS manifest_id",
            "job_order_details.item_name AS commodity_name",
            'customers.name AS receiver_name',
            DB::raw('true AS is_approved')
        );

        $deliveryOrder = DB::query()->fromSub($deliveryOrder, "delivery_orders");
        $deliveryOrder = $deliveryOrder->select(
            "manifest_id",
            DB::raw("JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'manifest_detail_id', manifest_detail_id,
                        'code', code,
                        'manifest_id', manifest_id,
                        'commodity_name', commodity_name,
                        'receiver_name', receiver_name
                    )
            ) AS items")
        );
        $deliveryOrder = $deliveryOrder->groupBy('manifest_id');


        $dt = DB::table('manifests');
        $dt = $dt->joinSub($deliveryOrder, "delivery_orders", function ($query){
            $query->on("delivery_orders.manifest_id", 'manifests.id');
        });
        $dt = $dt->select(
            'manifests.id AS manifest_id',
            'manifests.code AS manifest_code',
            'delivery_orders.items'
        );
        $dt = $dt->get();
        $dt = $dt->map(function ($v) {
            $v->items = json_decode($v->items);
            return $v;
        });
        $data = $dt;
        $resp['data'] = $data;

        return response()->json($resp);
    }

    public static function indexReceiver($id) {
        DeliveryOrderDriver::validate($id);
        $resp = [];
        $resp['message'] = 'OK';

        $dt = JobOrder::query(['delivery_order_id' => $id]);
        $dt = $dt->groupBy('customers.id');
        $dt = $dt->select(
            "job_orders.id AS id",
            "customers.id AS receiver_id",
            "customers.name AS receiver_name"
        );
        $dt = $dt->get();
        
        $data = $dt;
        $resp['data'] = $data;

        return response()->json($resp);
    }

    /*
      Date : 14-09-2020
      Description : Meng-update qty yang terangkut
      Developer : Didin
      Status : Create
    */
    public static function loadItem(Request $request, $id, $manifest_detail_id) {
        DeliveryOrderDriver::validate($id);
        $request->validate([
            'qty' => 'required|numeric'
        ]);

        $resp = [];
        $resp['message'] = 'OK';
        $resp['data'] = null;
        $status_code = 200;

        if($request->qty <= 0){
            $resp['message'] = 'Qty harus lebih dari 0';
            $resp['data'] = null;
            $status_code = 421;
            return response()->json($resp, $status_code);
        }

        DB::beginTransaction();
        try {
            ManifestDetail::updateTransportedQty($manifest_detail_id, $request->qty);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $resp['message'] = $e->getMessage();
            $status_code = 421;

        }

        return response()->json($resp, $status_code);
    }

    /*
      Date : 14-09-2020
      Description : Meng-update qty yang dibongkar
      Developer : Didin
      Status : Create
    */
    public static function dischargeItem(Request $request, $id, $manifest_detail_id) {
        DeliveryOrderDriver::validate($id);
        $resp = [];
        $resp['message'] = 'OK';
        $resp['data'] = null;
        $status_code = 200;

        $request->validate([
            'qty' => 'required|numeric'
        ]);

        if($request->qty <= 0){
            $resp['message'] = 'Qty harus lebih dari 0';
            $resp['data'] = null;
            $status_code = 421;
            return response()->json($resp, $status_code);
        }
        DB::beginTransaction();
        try {
            ManifestDetail::updateDischargedQty($manifest_detail_id, $request->qty);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $resp['message'] = $e->getMessage();
            $status_code = 421;

        }

        return response()->json($resp, $status_code);
    }

    /*
      Date : 14-09-2020
      Description : Meng-update qty yang dibongkar
      Developer : Didin
      Status : Create
    */
    public static function submitStatus(Request $request, $id) {
        DeliveryOrderDriver::validate($id);
        $resp = [];
        $resp['message'] = 'OK';
        $resp['data'] = null;
        $status_code = 200;
        DB::beginTransaction();
        try {
            DeliveryOrderDriver::updateToNextStatus($id, auth()->id(), $request->job_order_id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $resp['message'] = $e->getMessage();
            $status_code = 421;

        }

        return response()->json($resp, $status_code);
    }

    /*
      Date : 14-09-2020
      Description : Meng-update qty yang dibongkar
      Developer : Didin
      Status : Create
    */
    public static function storeFile(Request $request, $id) {
        $resp = [];
        $resp['message'] = 'OK';
        $resp['data'] = null;
        $status_code = 200;
        DB::beginTransaction();
        try {
            DeliveryOrderDriverDocument::store($id, $request->file('file'));
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $resp['message'] = $e->getMessage();
            $status_code = 421;

        }

        return response()->json($resp, $status_code);
    }

    /*
      Date : 21-07-2021
      Description : Menampilkan jumlah surat jalan hari inii
      Developer : Didin
      Status : Create
    */
    public static function showSummary($driver_id = null, $vehicle_id = null) {
        $resp['message']  = 'OK';
        $user = auth()->user();
        $vehicle = VehicleDriver::showVehicleByDriver($user->contact_id);
        $dt = [];
        $dt['job_today'] = DeliveryOrderDriver::amountToday($user->contact_id, $vehicle->id);
        $dt['job_this_month'] = DeliveryOrderDriver::amountThisMonth($user->contact_id, $vehicle->id);
        $resp['data'] = $dt;

        return response()->json($resp);
    }
}
