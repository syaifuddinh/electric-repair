<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Model\Manifest AS M;
use App\Abstracts\Operational\DeliveryOrderDriver;
use App\Abstracts\Operational\ManifestDetail;
use App\Abstracts\AdditionalField;
use App\Abstracts\JobOrderDetail;
use App\Abstracts\Contact;

class Manifest
{
    protected static $table = 'manifests';

    public static function query($request = []) {
        $request = self::fetchFilter($request);
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('routes', 'routes.id', self::$table . '.route_id');

        if($request['job_order_id']) {
            $details = ManifestDetail::query(['job_order_id' => $request['job_order_id']
            ]);
            $manifests = $details->pluck('manifest_details.header_id');
            $dt = $dt->whereIn(self::$table . '.id', $manifests);
        }


        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['job_order_id'] = $args['job_order_id'] ?? null;

        return $params;
    }

    public static function indexRequestedMail($source) {
        $dt = DB::table(self::$table);
        $sentManifest = DB::table('manifest_email_logs')->select('manifest_id');
        $sentManifest = $sentManifest->toSql();
        $dt = $dt->whereRaw("id NOT IN ($sentManifest)");

        if($source) {
            $dt = $dt->where(self::$table . '.source', $source);
        }

        $dt = $dt->get();

        return $dt;
    }

    /*
      Date : 17-02-2021
      Description : Menangkap parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($params = []) {
        $args = [];
        $args['company_id'] = $params['company_id'] ?? null;
        $args['vehicle_type_id'] = $params['vehicle_type_id'] ?? null;
        $args['route_id'] = $params['route_id'] ?? null;
        $args['vehicle_id'] = $params['vehicle_id'] ?? null;
        $args['reff_no'] = $params['reff_no'] ?? null;
        $args['driver_id'] = $params['driver_id'] ?? null;
        $args['date_manifest'] = $params['date_manifest'] ?? null;
        $args['date_manifest'] = Carbon::parse($args['date_manifest']);
        $args['description'] = $params['description'] ?? null;
        $etd_date = $params['etd_date'] ?? null;
        $etd_time = $params['etd_time'] ?? null;
        $args['etd_time'] = createTimestamp($etd_date, $etd_time);
        $eta_date = $params['eta_date'] ?? null;
        $eta_time = $params['eta_time'] ?? null;
        $args['eta_time'] = createTimestamp($eta_date, $eta_time);
        return $args;
    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberaraan data manifest
      Developer : Didin
      Status : Create
    */
    public static function validate($id, $source = null, $is_crossdocking = 0) {
        $dt = DB::table('manifests')
        ->whereId($id);
        if($source) {
            switch($source) {
                case 'job_order' :
                    $dt->where('manifests.source', $source);
                    break;
                case 'picking_order' :
                    $dt->where('manifests.source', $source);
                    break;
            }
        }

        if($is_crossdocking) {
            $dt = $dt->where('manifests.is_crossdocking', 1);
        }

        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    public static function show($id, $source = null, $is_crossdocking = null) {
        self::validate($id, $source, $is_crossdocking);
        $dt = M::with('vehicle_type','delivery','container','container_type','company:id,name','trayek:id,name')->where('manifests.id', $id);

        if($source) {
            switch($source) {
                case 'job_order' :
                    $dt->where('manifests.source', $source);
                    break;
                case 'picking_order' :
                    $dt->where('manifests.source', $source);
                    break;
            }
        }

        if($is_crossdocking) {
            $dt = $dt->where('manifests.is_crossdocking', 1);
        }

        $dt = $dt->first();
        $dt->additional = json_decode($dt->additional);
        $inManifest = AdditionalField::indexKey('jobOrder', ['show_in_manifest' => 1]);
        if(count($inManifest) > 0) {
            $jo = JobOrderDetail::query();
            $jo->where('manifest_details.header_id', $id);
            foreach($inManifest as $m) {
                $jo = $jo->addSelect([DB::raw("GROUP_CONCAT(REPLACE(JSON_EXTRACT(job_orders.additional, '$.$m'), '\"', '') SEPARATOR ',') AS $m")]);
            }
            $dt->job_order = $jo->first();
        }

        $dt->delivery_order_driver_id = DeliveryOrderDriver::getIdByManifest($id);

        return $dt;
    }

    /*
      Date : 12-02-2021
      Description : Menghapus manifest
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::beginTransaction();
        $deliveryOrder = DeliveryOrderDriver::showByManifest($id);
        if($deliveryOrder) {
            DB::table('vehicles')
            ->whereDeliveryId($deliveryOrder->id)
            ->update(['delivery_id' => null]);
            DeliveryOrderDriver::destroy($deliveryOrder->id);
        }
        DB::table('manifests')
        ->whereId($id)
        ->delete();

        DB::commit();
    }

    /*
      Date : 12-02-2021
      Description : Meng-update manifest
      Developer : Didin
      Status : Create
    */
    public static function update($args = [], $id) {
        self::validate($id);
        DB::beginTransaction();
        $params = self::fetch($args);
        DB::table('manifests')
        ->whereId($id)
        ->update($params);

        DB::commit();
    }

    /*
      Date : 12-02-2021
      Description : Menyimpan data additional
      Developer : Didin
      Status : Create
    */
    public static function storeAdditional($params = [], $id) {
        $manifest = self::show($id);
        $origin = $manifest->additional;


        $keys = collect(array_keys($params));
        $manifestKeys = AdditionalField::indexKey('manifest');
        $keys = $keys->intersect($manifestKeys);

        $data = [];

        foreach ($keys as $k) {
            $data[$k] = $params[$k];
        }

        $params = $data;
        $params = collect($params)->union($origin);
        $params = $params->all();
        $json = json_encode($params);
        $update['additional'] = $json;
        DB::table('manifests')
        ->whereId($id)
        ->update($update);
    }


    public static function setNullableAdditional($id) {
        $dt = self::show($id);
        if(!$dt->additional) {
            $data['additional'] = '{}';
            DB::table('manifests')
            ->whereId($id)
            ->update($data);
        }
    }

    public static function getCustomers($id) {
        $dt = self::show($id);
        $customers = [];

        $details = ManifestDetail::query(['header_id' => $id]);
        $details = $details->select('job_orders.customer_id')->groupBy('job_orders.customer_id');
        $customers = $details->pluck('job_orders.customer_id')->toArray();

        return $customers;
    }

    public static function getEmails($id) {
        $customers = self::getCustomers($id);
        $emails = Contact::getEmails($customers);

        return $emails;
    }
}
