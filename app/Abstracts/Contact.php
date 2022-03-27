<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Setting\Setting;
use App\Abstracts\Finance\Receivable;
use App\Abstracts\Finance\Payable;
use App\Abstracts\Operational\DeliveryOrderDriver;
use App\Abstracts\Vehicle\Vehicle;
use App\Abstracts\Vehicle\VehicleDriver;

class Contact
{
    protected static $table = 'contacts';

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Contact not found');
        }
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi limit piutang
      Developer : Didin
      Status : Create
    */
    /*
      Date : 22-07-2021
      Description : Menambahkan getResponse untuk mendapatkan apakah melebihi piutang atau tidak
      Developer : Hendra
      Status : Edit/Update
    */
    public static function validasiLimitPiutang($id, $revenue = 0, $getResponse = false) {
        $dt = self::show($id);
        if(Setting::fetchValue('job_order', 'validasi_limit_piutang') == 1) {
            $isExceed = false;
            if($dt->is_pelanggan == 1) {
                $limit = $dt->limit_piutang;
                $revenue += Receivable::getSisa(['customer_id' => $id]);
                if($limit > 0) {
                    if($revenue > $limit) {
                        $isExceed = true;
                        if(!$getResponse){
                            throw new Exception('Customer ' . $dt->name . ' has been exceed receivable limit / limit piutang');
                        }
                    }
                }
            }
            return $isExceed;
        }
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi limit hutang
      Developer : Didin
      Status : Create
    */
    public static function validasiLimitHutang($id, $revenue = 0) {
        $dt = self::show($id);
        if(Setting::fetchValue('job_order', 'validasi_limit_hutang') == 1) {
            if($dt->is_vendor == 1 || $dt->is_supplier == 1) {
                $limit = $dt->limit_hutang;
                $leftover = Payable::getSisa(['contact_id' => $id]);
                $revenue += $leftover;
                if($limit > 0) {
                    if($revenue > $limit) {
                        throw new Exception('Vendor ' . $dt->name . ' has been exceed payable limit / limit hutang');
                    }
                }
            }
        }
    }

    /*
      Date : 29-08-2020
      Description : Generate vendor price
      Developer : Didin
      Status : Create
    */
    public static function showVendor() {
        $dt = DB::table('contacts')
        ->whereIsVendor(1)
        ->whereVendorStatusApprove(2)
        ->select('id', 'name')
        ->get();

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Mendapatkan email
      Developer : Didin
      Status : Create
    */
    public static function getEmails($id) {
        $email = null;

        if(is_array($id)) {
            $dt = DB::table(self::$table);
            $dt = $dt->whereNotNull('email');
            $dt = $dt->whereIn('id', $id);
            $dt = $dt->selectRaw("GROUP_CONCAT(email SEPARATOR ';') AS email");
            $dt = $dt->first();
            $email = $dt->email;
        } else {
            $dt = self::show($id);
            $email = $dt->email;
        }

        return $dt;
    }

    public static function fetchAvailableVehicleQuery($args) {
        $params = [];
        $params['contact_id'] = $args['contact_id'] ?? null;

        return $params;
    }

    /*
      Date : 09-07-2021
      Description : Melihat daftar kendaraan yang di-assign ke driver 
      Developer : Didin
      Status : Create
    */
    public static function availableVehicleQuery($request = []) {
        $request = self::fetchAvailableVehicleQuery($request);
        $params = [
            'job_status_slug' => 'startedByDriver'
        ];
        if($request['contact_id']) {
            $params['driver_id'] = $request['contact_id'];
        }
        $dod = DeliveryOrderDriver::query($params);

        $dod = $dod->select(
            'vehicles.id'
        );
        $dod = $dod->pluck('vehicles.id');
        $dt = Vehicle::query();
        $dt = $dt->whereIn('vehicles.id', $dod);

        return $dt;
    }
}
