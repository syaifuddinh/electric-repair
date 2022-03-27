<?php

namespace App\Abstracts\Sales;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\JobOrderDetail;
use App\Abstracts\Sales\SalesOrder;
use App\Abstracts\Inventory\Item;
use stdClass;

class SalesOrderDetail extends JobOrderDetail
{
    public static function query($params = []) {
        $request = self::fetchFilter($params);

        $used = DB::table('manifest_details')
        ->join('job_order_details', 'job_order_details.id', 'manifest_details.job_order_detail_id')
        ->join('job_orders', 'job_orders.id', 'job_order_details.header_id')
        ->join('sales_orders', 'job_orders.id', 'sales_orders.job_order_id')
        ->groupBy('manifest_details.job_order_detail_id')
        ->select('manifest_details.job_order_detail_id', DB::raw('SUM(IF(manifest_details.requested_qty > 0, manifest_details.requested_qty, manifest_details.transported)) AS transported'));

        $dt = DB::table(parent::$table);
        $dt = $dt->join('job_orders', 'job_orders.id', parent::$table . '.header_id');
        $dt = $dt->join('sales_orders', 'sales_orders.job_order_id', 'job_orders.id');
        $dt = $dt->join('contacts', 'contacts.id', 'job_orders.customer_id');

        $dt = $dt->leftJoinSub($used, 'used', function($query){
            $query->on('used.job_order_detail_id', 'job_order_details.id');
        });

        if($request['except']) {
            $dt = $dt->whereNotIn(self::$table . ".id", $request['except']);
        }

        if($request['customer_id']) {
            $dt = $dt->where("job_orders.customer_id", $request['customer_id']);
        }

        if($request['is_pallet'] == 1) {
            $pallet = Item::query(['is_pallet' => 1]);
            $pallet = $pallet->pluck('items.id');
            $dt = $dt->whereIn(self::$table . '.item_id', $pallet);
        }


        $dt = $dt->select(parent::$table . '.id', parent::$table . '.header_id', parent::$table . '.item_name', parent::$table . '.price', parent::$table . '.qty', 'contacts.name AS customer_name', 'sales_orders.id AS sales_order_id', 'sales_orders.code AS sales_order_code', DB::raw(parent::$table . ".qty - COALESCE(used.transported, 0) AS leftover"));

        return $dt;
    } 

    public static function fetchFilter($args = []) {
        $params = [];
        $params["is_pallet"] = $args["is_pallet"] ?? null;
        $params["customer_id"] = $args["customer_id"] ?? null;
        $params["except"] = $args["except"] ?? [];
        if(!is_array($params['except'])) {
            $params['except'] = [];
        }

        return $params;
    }

    public static function show($id) {
        $dt = parent::show($id);
        $so = SalesOrder::showByJobOrder($dt->header_id);
        $dt->code = $so->code;

        return $dt;
    }

    /*
      Date : 19-04-2021
      Description : Menampilkan daftar item sales order
      Developer : Didin
      Status : Create
    */
    public static function index($id = null) {
        $job_order_id = null;
        if($id) {
            $so = SalesOrder::show($id);
            $job_order_id = $so->job_order_id;
        }
        $dt = parent::index($job_order_id);
        $dt = $dt->map(function($val){
            $res = new stdClass();
            $res->job_order_detail_id = $val->id;
            $res->item_id = $val->item_id;
            $res->item_name = $val->item_name;
            $res->piece_name = $val->piece;
            $res->imposition_name = $val->imposition;
            $res->description = $val->description;
            $res->qty = $val->qty;
            $res->price = $val->price;
            $res->total_price = $val->total_price;

            return $res;
        });

        return $dt;
    }
}
