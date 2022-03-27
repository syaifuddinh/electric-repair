<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Contact;

class VendorPrice
{
    /*
      Date : 29-08-2020
      Description : Generate vendor price
      Developer : Didin
      Status : Create
    */
    public static function index($cost_type_id = null) {
        $dt = DB::table('vendor_prices')
        ->whereIsUsed(1);
        if($cost_type_id) {
            $dt = $dt->where('vendor_prices.cost_type_id', $cost_type_id);
        }
        $dt = $dt->get();

        return $dt;
    }
}
