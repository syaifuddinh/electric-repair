<?php

namespace App\Abstracts\Vehicle;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Vehicle\Vehicle;
use App\Abstracts\Contact;

class VehicleDriver
{
    protected static $table = 'vehicle_drivers';

    /*
      Date : 29-08-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table(self::$table);

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail
      Developer : Didin
      Status : Create
    */
    public static function showVehicleByDriver($driver_id) {
        $r = null;
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.driver_id', $driver_id);
        $dt = $dt->first();
        if($dt) {
            $r = Vehicle::show($dt->vehicle_id);
        }

        return $r;
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi picking detail
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 23-03-2021
      Description : Mengambil parameter untuk input data
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params['company_id'] = $args['company_id'] ?? null;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['date_transaction'] = $args['date_transaction'] ?? null;
        $params['description'] = $args['description'] ?? null;
        $params['create_by'] = $args['create_by'] ?? null;

        if(!$params['company_id']) {
            throw new Exception('Branch is required');
        }

        if(!$params['warehouse_id']) {
            throw new Exception('Warehouse is required');
        }

        if(!$params['date_transaction']) {
            throw new Exception('Date transaction is required');
        } else {
            $params['date_transaction'] = Carbon::parse($params['date_transaction']);
        }

        return $params;
    }

    public static function validateUsedVehicle($driver_id, $vehicle_id) {
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.vehicle_id', $vehicle_id);
        $dt = $dt->where(self::$table . '.driver_id', '!=', $driver_id);

        $exist = $dt->count(self::$table . '.id');
        if($exist > 0) {
            throw new Exception('Vehicle was used');
        }
    }

    /*
      Date : 23-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($driver_id, $vehicle_id) {
        if(!$driver_id) {
            throw new Exception('Driver is required');
        }
        if(!$vehicle_id) {
            throw new Exception('Vehicle is required');
        }
        Contact::validate($driver_id);
        Vehicle::validate($vehicle_id);
        $driverExist = DB::table(self::$table);
        $driverExist = $driverExist->whereDriverId($driver_id);
        $driverExist = $driverExist->count(self::$table . '.id');

        self::validateUsedVehicle($driver_id, $vehicle_id);

        if($driverExist == 0) {
            DB::table(self::$table)->insert([
                'driver_id' => $driver_id,
                'vehicle_id' => $vehicle_id
            ]);
        } else {            
            DB::table(self::$table)
            ->whereDriverId($driver_id)
            ->update([
                'vehicle_id' => $vehicle_id
            ]);
        }
    }

    public static function validateHasShipment() {
        
    }

    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params = [], $id) {
        self::validate($id);
        $detail = $params ['detail'] ?? null;
        $update = self::fetch($params);

        DB::table(self::$table)
        ->whereId($id)
        ->update($update);

        if($detail && is_array($detail)) {
            PickingDetail::clearStock($id);
            PickingDetail::clear($id);

            PickingDetail::storeMultiple($detail, $id);
        }
    }

    /*
      Date : 23-03-2021
      Description : Memperoleh status yang tipe nya disetujui
      Developer : Didin
      Status : Create
    */
    public static function getApproveStatus() {
        return 2;
    }

    /*
      Date : 23-03-2021
      Description : Memvalidasi apakah data sudah disetujui atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsApproved($id) {
        $dt = self::show($id);
        $approveStatus = self::getApproveStatus();
        if($dt->status == $approveStatus) {
            throw new Exception('Data was approved');
        }
    }

    /*
      Date : 23-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        DB::table(self::$table)->whereId($id)->delete();
    }
}
