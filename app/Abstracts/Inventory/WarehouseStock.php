<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Inventory\WarehouseStockDetail;

class WarehouseStock
{
    /*
      Date : 19-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $item = WarehouseStockDetail::stocklist();
        $item = $item->select(
            'warehouse_id', 
            'item_id',
            DB::raw('SUM(qty) AS qty')
        );

        $params = self::fetchFilter($params);
        if($params['group_by_item'] == 1) {
            $item = $item->groupBy(
                'item_id'
            );
        } else {
            $item = $item->groupBy(
                'item_id', 
                'warehouse_id'
            );
        }

        $dt = DB::query()->fromSub($item, 'warehouse_stocks');
        $dt = $dt->join('warehouses', 'warehouses.id', 'warehouse_stocks.warehouse_id');
        $dt = $dt->join('companies', 'companies.id', 'warehouses.company_id');
        $dt = $dt->leftJoin('items', 'warehouse_stocks.item_id', 'items.id');
        $dt = $dt->leftJoin('categories', 'categories.id', 'items.category_id');



        $dt = self::filterQuery($params, $dt);

        return $dt;
    }

    /*
      Date : 19-03-2021
      Description : Filter query
      Developer : Didin
      Status : Create
    */
    public static function filterQuery($params, $dt) {
        if($params['warehouse_id']) {
            $dt = $dt->where('warehouse_stocks.warehouse_id', $params['warehouse_id']);
        }

        if($params['start_qty']) {
            $dt = $dt->where('warehouse_stocks.qty', '>=', $params['start_qty']);
        }

        if($params['end_qty']) {
            $dt = $dt->where('warehouse_stocks.qty', '<=', $params['end_qty']);
        }

        if($params['is_merchandise']) {
            $dt = $dt->where('items.is_merchandise', '=', $params['is_merchandise']);
        }

        return $dt;
    }

    /*
      Date : 19-03-2021
      Description : Mengambil parameter untuk filter data
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($args = []) {
        $params = [];
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['start_qty'] = $args['start_qty'] ?? null;
        $params['end_qty'] = $args['end_qty'] ?? null;
        $params['group_by_item'] = $args['group_by_item'] ?? null;
        $params['is_merchandise'] = $args['is_merchandise'] ?? null;

        return $params;
    }
}
