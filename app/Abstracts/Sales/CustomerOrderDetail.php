<?php

namespace App\Abstracts\Sales;

use App\Abstracts\JobOrderDetail;
use App\Abstracts\Sales\CustomerOrder;
use Illuminate\Support\Facades\DB;
use stdClass;

class CustomerOrderDetail extends CustomerOrder
{
    protected static $table_detail = 'customer_order_details';

    public static function query($params = []) {
        $request = self::fetchFilter($params);
        $dt = DB::table(self::$table_detail);
        $dt = $dt->join(self::$table, self::$table_detail.'.header_id', parent::$table . '.id');
        $dt = $dt->join('items', self::$table_detail.'.item_id', 'items.id');
        $dt = $dt->join('pieces', 'items.piece_id', 'pieces.id');

        if($request['header_id']) {
            $dt = $dt->where(self::$table_detail.".header_id", $request['header_id']);
        }

        $dt = $dt->select(self::$table_detail.'.*', 'items.name', 'items.code', DB::raw(self::$table_detail.'.qty * '.self::$table_detail.'.price as total_price'), 'pieces.id as piece_id', 'pieces.name as unit');
        
        return $dt;
    } 

    public static function fetchFilter($args = []) {
        $params = [];
        $params["header_id"] = $args["header_id"] ?? null;

        return $params;
    }

    /*
      Date : 08-07-2021
      Description : Menampilkan daftar detail customer order
      Developer : Hendra
      Status : Create
    */
    public static function index($id = null) {
        $params = [
            'header_id' => $id
        ];
        $dt = self::query($params)->get();

        return $dt;
    }
}
