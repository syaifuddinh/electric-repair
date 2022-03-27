<?php

namespace App\Abstracts\Setting\Operational;

use DB;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Abstracts\VendorPrice;
use App\Abstracts\Setting\Math;
use App\Abstracts\Contact;
use App\Http\Controllers\Marketing\VendorPriceController;

class CostType 
{
    protected static $table = 'cost_types';

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan detail jenis biaya / cost
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table);
        $dt = $dt->leftJoin('cost_route_types', self::$table . '.cost_route_type_id', 'cost_route_types.id');
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->select(
            self::$table . '.*',
            'cost_route_types.slug AS cost_route_type_slug'
        );
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Generate vendor price
      Developer : Didin
      Status : Create
    */
    public static function generateVendorPrice($id) {
        $vendors = Contact::showVendor();
        $cost_type = DB::table('cost_types')
        ->whereId($id)
        ->whereNotNull('parent_id')
        ->first();

        if($cost_type) {
            $vendor_prices = VendorPrice::index($id);
            $vendorPriceController = new VendorPriceController();
            $price = $cost_type->is_bbm==1?$cost_type->cost:$cost_type->initial_cost;
            if(count($vendor_prices) > 0) {
                $vendor_prices->map(function($vp) use($price, $cost_type, $vendorPriceController) {
                    $params = [];
                    $params['company_id'] = $cost_type->company_id;
                    $params['cost_category'] = 1;
                    $params['cost_type_id'] = $cost_type->id;
                    $params['vendor_id'] = $cost_type->vendor_id;
                    $params['date'] = Carbon::now()->format('d-m-Y');
                    $params['price_full'] = $price;
                    $vendorPriceController->update(new Request($params), $vp->id);
                });
            } else {
                $vendors->map(function($v) use($cost_type, $vendorPriceController, $price) {
                    $params = [];
                    $params['company_id'] = $cost_type->company_id;
                    $params['cost_category'] = 1;
                    $params['vendor_id'] = $v->id;
                    $params['cost_type_id'] = $cost_type->id;
                    $params['date'] = Carbon::now()->format('d-m-Y');
                    $params['price_full'] = $price;
                    $vendorPriceController->store(new Request($params));
                });
            }
        }
    }

    public static function getTotalPrice($id, $qty, $price = 0) {
        $r = 0;
        $qty = $qty ?? 0;
        $dt = self::show($id);
        if(!$price || $price == 0) {
            $price = $dt->initial_cost;
        }
        $r = $qty * $price;
        $r = Math::adjustFloat($r);

        

        return $r;
    }
}
