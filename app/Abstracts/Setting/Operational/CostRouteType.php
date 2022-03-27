<?php

namespace App\Abstracts\Setting\Operational;

use DB;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Abstracts\VendorPrice;
use App\Abstracts\Contact;
use App\Http\Controllers\Marketing\VendorPriceController;

class CostRouteType 
{
    protected static $table = 'cost_route_types';

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function index() {
        $dt = DB::table(self::$table);
        $dt = $dt->get();

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
}
