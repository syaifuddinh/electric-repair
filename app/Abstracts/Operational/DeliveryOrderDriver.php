<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Model\delivery_order_driver AS M;
use App\Abstracts\Setting\JobStatus;
use App\Abstracts\Operational\ManifestDetail;
use App\Abstracts\Operational\DeliveryOrderStatusLog;
use App\Abstracts\Operational\DeliveryOrderOngoingJob;
use App\Abstracts\Operational\Manifest;
use App\Abstracts\Contact\ContactLocation;

class DeliveryOrderDriver
{
    protected static $table = 'delivery_order_drivers';

    public static function query($params = []) {
        $wr="1=1";

        $request = self::fetchFilter($params);

        if (auth()->user()->is_admin==0) {
            $wr.=" AND manifests.company_id = ".auth()->user()->company_id;
        }
        if ($request['status']) {
            $wr.=" and delivery_order_drivers.job_status_id = ".$request['status'];
        }

        if ($request['vehicle_id']) {
            $wr.=" and delivery_order_drivers.vehicle_id = " . $request['vehicle_id'];
        }

        $item = DB::table(self::$table)
        ->leftJoin('manifests','manifests.id','delivery_order_drivers.manifest_id')
        ->leftJoin('routes','routes.id','manifests.route_id')
        ->leftJoin('job_statuses','job_statuses.id','delivery_order_drivers.job_status_id')
        ->leftJoin('contacts as driver','driver.id','delivery_order_drivers.driver_id')
        ->leftJoin('vehicles','vehicles.id','delivery_order_drivers.vehicle_id')
        ->whereRaw($wr);

        if ($request['start_date']) {
            $start = Carbon::parse($request['start_date'])->format('Y-m-d');
            $item = $item->where(DB::raw('DATE_FORMAT(' . self::$table . '.pick_date, "%Y-%m-%d")'), '>=', $start);            
        }

        if ($request['end_date']) {
            $end = Carbon::parse($request['end_date'])->format('Y-m-d');
            $item = $item->where(DB::raw('DATE_FORMAT(' . self::$table . '.pick_date, "%Y-%m-%d")'), '<=', $end);            
        }

        if($request['job_status_slug']) {
            $item = $item->where('job_statuses.slug', $request['job_status_slug']);
        }

        if($request['job_order_id']) {
            $manifests = Manifest::query([
                'job_order_id' => $request['job_order_id']
            ]);
            $manifests = $manifests->pluck('manifests.id');
            $item = $item->whereIn(self::$table . '.manifest_id', $manifests);
        }

        if($request['driver_id']) {
            $item = $item->where(self::$table . '.driver_id', $request['driver_id']);
        }

        return $item;
    }

    public static function fetchFilter($args = []) {
        $params = [];
        $params['status'] = $args['status'] ?? null;
        $params['job_status_slug'] = $args['job_status_slug'] ?? null;
        $params['vehicle_id'] = $args['vehicle_id'] ?? null;
        $params['start_date'] = $args['start_date'] ?? null;
        $params['end_date'] = $args['end_date'] ?? null;
        $params['driver_id'] = $args['driver_id'] ?? null;
        $params['job_order_id'] = $args['job_order_id'] ?? null;

        return $params;
    }

    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Delivery order not found');
        }
    }

    /*
      Date : 19-02-2021
      Description : Menampilkan detail surat jalan driver berdasarkan manifest
      Developer : Didin
      Status : Create
    */
    public static function showByManifest($manifest_id) {
        $dt = DB::table(self::$table)
        ->whereManifestId($manifest_id);

        $dt = $dt->first();

        return $dt;
    }

    public static function getIdByManifest($manifest_id) {
        $r = null;

        $dt = self::showByManifest($manifest_id);
        if($dt) {
            $r = $dt->id;
        }

        return $r;
    }

    /*
      Date : 19-02-2021
      Description : Menampilkan detail surat jalan driver
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $job_statuses = JobStatus::query();
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('vehicles', 'vehicles.id', self::$table . '.vehicle_id');
        $dt = $dt->leftJoin('contacts AS drivers', 'drivers.id', self::$table . '.driver_id');
        $dt = $dt->leftJoin('manifests', 'manifests.id', self::$table . '.manifest_id');
        $dt = $dt->leftJoin('routes', 'routes.id', 'manifests.route_id');
        $dt = $dt->leftJoinSub($job_statuses, 'job_statuses', function ($query){
            $query->on(self::$table . '.job_status_id', 'job_statuses.id');
        });
        $dt = $dt->where(self::$table . '.id', $id);

        $dt = $dt->select(
            self::$table . '.id',
            self::$table . '.code',
            self::$table . '.pick_date',
            self::$table . '.driver_id',
            DB::raw(self::$table . '.journey_distance / 1000 AS journey_distance'),
            'manifests.code AS manifest_code',
            'routes.name AS route_name',
            'drivers.name AS driver_name',
            'vehicles.nopol',
            'job_statuses.id AS status',
            'job_statuses.slug AS status_slug',
            'job_statuses.is_finish',
            'job_statuses.next_status_id AS next_status',
            'job_statuses.name AS status_name',
            'job_statuses.next_status_name AS next_status_name',
            'routes.name AS route'
        );

        $dt = $dt->first();

        return $dt;
    }

    public static function destroy($id) {
        self::validate($id);
        DB::beginTransaction();

        DB::table(self::$table)
        ->whereId($id)
        ->delete();

        DB::commit();
    }

    /*
      Date : 19-02-2021
      Description : Update ke status selanjutnya
      Developer : Didin
      Status : Create
    */
    public static function updateToNextStatus($id, $created_by) {
        $dt = self::show($id);
        if($dt->is_finish) {
            throw new Exception('Shipment was finished');
        }
        if($dt->next_status) {
            $params = [];
            $params['created_by'] = $created_by;
            $params['job_status_id'] = $dt->next_status;
            $params['delivery_order_driver_id'] = $id;
            DeliveryOrderStatusLog::store($params);

            DB::table(self::$table)->whereId($id)->update([
                'job_status_id' => $dt->next_status
            ]);
        }
    }

    /*
      Date : 21-07-2021
      Description : Menampilkan jumlah surat jalan hari inii
      Developer : Didin
      Status : Create
    */
    public static function amountToday($driver_id = null, $vehicle_id = null) {
        $params = [];
        $params['start_date'] = Carbon::now()->format('Y-m-d');
        $params['end_date'] = Carbon::now()->format('Y-m-d');
        if($driver_id) {
            $params['driver_id'] = $driver_id;
        }
        if($vehicle_id) {
            $params['vehicle_id'] = $vehicle_id;
        }

        $dt = self::query($params);
        $r = $dt->count(self::$table . '.id');
        return $r;
    }

    /*
      Date : 21-07-2021
      Description : Menampilkan jumlah surat jalan hari inii
      Developer : Didin
      Status : Create
    */
    public static function amountThisMonth($driver_id = null, $vehicle_id = null) {
        $params = [];
        $params['start_date'] = Carbon::now()->startOfMonth()->format('Y-m-d');
        $params['end_date'] = Carbon::now()->endOfMonth()->format('Y-m-d');
        if($driver_id) {
            $params['driver_id'] = $driver_id;
        }
        if($vehicle_id) {
            $params['vehicle_id'] = $vehicle_id;
        }

        $dt = self::query($params);
        $r = $dt->count(self::$table . '.id');
        return $r;
    }

    /*
      Date : 21-07-2021
      Description : Menampilkan summary pengiriman yang sudah selesai
      Developer : Didin
      Status : Create
    */
    public static function indexShipmentSummary($driver_id = null, $vehicle_id = null) {
        $params = [];
        $params['start_date'] = Carbon::now()->startOfYear()->format('Y-m-d');
        $params['end_date'] = Carbon::now()->endOfYear()->format('Y-m-d');
        if($driver_id) {
            $params['driver_id'] = $driver_id;
        }
        if($vehicle_id) {
            $params['vehicle_id'] = $vehicle_id;
        }
        $params['job_status_slug'] = 'jobFinished';

        $deliveryOrder = self::query($params);
        $deliveryOrder = $deliveryOrder->select(
            self::$table . '.id', 
            DB::raw("DATE_FORMAT(" . self::$table . ".pick_date, '%m') AS month_sequence"),
            DB::raw("DATE_FORMAT(" . self::$table . ".pick_date, '%Y') AS year")
        );
        $shipments = DB::query()->fromSub($deliveryOrder, 'shipments');
        $shipments = $shipments->groupBy('month_sequence', 'year');
        $shipments = $shipments->select(
            "month_sequence",
            "year",
            DB::raw('COUNT(id) AS qty_shipment')
        );

        $months = DB::table('months');
        $months = $months->select(
            DB::raw('name AS `months`'),
            DB::raw('months.sequence AS month_sequence'),
            DB::raw('DATE_FORMAT(NOW(), "%Y") AS `this_year`'),
        );
        $dt = DB::query()->fromSub($months, 'months');
        $dt = $dt->leftJoinSub($shipments, 'shipments', function ($query){
            $query->on('shipments.month_sequence', 'months.month_sequence');
            $query->on('shipments.year', 'months.this_year');
        });

        $dt = $dt->select(
            DB::raw('0 AS point'),
            'months.months',
            DB::raw('COALESCE(shipments.qty_shipment, 0) AS qty_shipment'),
            DB::raw('DATE_FORMAT(NOW(), CONCAT("%Y-", months.month_sequence, "-01")) AS actual_date')
        );
        $dt = $dt->orderBy('months.month_sequence', 'ASC');
        $dt = $dt->get();

        return $dt;
    }

    public static function showOrigin($id) {
        self::validate($id);
        $dt = ManifestDetail::query(['delivery_order_id' => $id]);
        $dt = $dt->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id');
        $dt = $dt->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id');
        $dt = $dt->join('warehouses', 'warehouses.id', 'warehouse_receipts.warehouse_id');

        $dt = $dt->select(
            'warehouses.name',
            'warehouses.address',
            'warehouses.latitude',
            'warehouses.longitude'
        );

        $dt = $dt->first();
        if(!$dt) {
            $dt = [
                'name' => null,
                'address' => null,
                'latitude' => null,
                'longitude' => null
            ];
            $dt = json_decode(json_encode($dt));
        }

        return $dt;
    }

    public static function showReceiver($id) {
        self::validate($id);
        $dt = DeliveryOrderOngoingJob::query([
            'delivery_order_driver_id' => $id
        ]);

        $dt = $dt->select(
            'customers.name',
            'customers.address',
            'customers.latitude',
            'customers.longitude'
        );

        $dt = $dt->first();
        if(!$dt) {
            $dt = [
                'name' => null,
                'address' => null,
                'latitude' => null,
                'longitude' => null
            ];
            $dt = json_decode(json_encode($dt));
        }

        return $dt;
    }

    public static function setJourneyDistance($driver_id) {
        $start_status_urut = 0;
        $end_status_urut = 0;

        $start_status = JobStatus::showBySlug('startedByDriver');
        if($start_status) {
            $start_status_urut = $start_status->urut;
        }

        $end_status = JobStatus::showBySlug('jobFinished');
        if($end_status) {
            $end_status_urut = $end_status->urut;
        }

        $dt = DB::table(self::$table);
        $dt = $dt->join('delivery_order_status_logs', 'delivery_order_status_logs.delivery_order_driver_id', self::$table . '.id');
        $dt = $dt->join('job_statuses', 'job_statuses.id', 'delivery_order_status_logs.job_status_id');
        $dt = $dt->where(self::$table . '.driver_id', $driver_id);
        $dt = $dt->where('job_statuses.urut', '>', $start_status_urut);
        $dt = $dt->where('job_statuses.urut', '<', $end_status_urut);
        $dt = $dt->select(self::$table . '.id');
        $dt = $dt->get();
        $dt->each(function($v) use($driver_id){
            $start_data = DeliveryOrderStatusLog::getFirstIndex($v->id);
            $end_data = DeliveryOrderStatusLog::getLastIndex($v->id);
            $end_time = $end_data->created_at;
            $end_status = JobStatus::show($end_data->job_status_id);
            if(
                $end_status->slug !=  'jobFinished' && 
                $end_status->slug !=  'aborted' && 
                $end_status->slug !=  'rejected'
            ) {
                $end_time = Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s');
            }

            $journey_distance = ContactLocation::getDistance(
                $start_data->created_at, 
                $end_time, 
                $driver_id
            );
            DB::table(self::$table)
            ->whereId($v->id)
            ->update([
                'journey_distance' => $journey_distance
            ]);
        });
    }   
}
