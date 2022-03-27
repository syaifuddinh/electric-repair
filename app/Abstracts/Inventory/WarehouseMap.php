<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Inventory\Warehouse;
use App\Abstracts\Inventory\WarehouseStockDetail;
use App\Abstracts\Inventory\RackMap;

class WarehouseMap
{
    // Property ini berisi character ASCII
    protected static $startRow = 65;
    protected static $startColumn = 65;
    // Property ini berisi char
    protected static $separatorLevel = '-';

    /*
      Date : 29-08-2021
      Description : Menampilkan daftar map
      Developer : Didin
      Status : Create
    */
    public static function index($warehouse_id) {
        $count_items = WarehouseStockDetail::query();
        $count_items = $count_items->where('racks.warehouse_id', $warehouse_id);
        $count_items = $count_items->groupBy('warehouse_stock_details.rack_id');
        $count_items = $count_items->select('warehouse_stock_details.rack_id', DB::raw('COUNT(warehouse_stock_details.id) AS qty_item'));

        $levels = self::query();
        $levels = $levels->leftJoin('rack_maps', 'rack_maps.warehouse_map_id', 'warehouse_maps.id');
        $levels = $levels->leftJoinSub($count_items, 'count_items', function($join){
            $join->on('rack_maps.rack_id', 'count_items.rack_id');
        });
        $levels = $levels->select('row', 'warehouse_id', 'column', 'level', DB::raw('JSON_ARRAYAGG(JSON_OBJECT("id", warehouse_maps.id, "level", warehouse_maps.`level`, "code", warehouse_maps.`code`, "rack_id", rack_maps.rack_id, "qty_item", COALESCE(count_items.qty_item, 0))) AS levels'));
        $levels = $levels->where('warehouse_maps.warehouse_id', $warehouse_id);
        $levels = $levels->groupBy('warehouse_maps.column', 'warehouse_maps.row', 'warehouse_maps.warehouse_id');

        $rows = DB::query()->fromSub($levels, 'm_levels');
        $rows = $rows->where('m_levels.warehouse_id', $warehouse_id);
        $rows = $rows->groupBy('m_levels.warehouse_id', 'm_levels.row');
        $rows = $rows->select('m_levels.row', 'm_levels.warehouse_id', DB::raw('JSON_ARRAYAGG(JSON_OBJECT("column", `m_levels`.`column`, "levels", `m_levels`.`levels`)) AS columns'));

        $dt = $rows->get();
        $dt = $dt->map(function($x){
            $x->columns = json_decode($x->columns);
            return $x;
        });

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menampikan daftar map warehouse
      Developer : Didin
      Status : Create
    */
    public static function list($warehouse_id) {
        $dt = self::query();
        $dt = $dt->where('warehouse_maps.warehouse_id', $warehouse_id);
        $dt = $dt->select('warehouse_maps.id', 'warehouse_maps.code AS name');
        $dt = $dt->get();

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Generate code
      Developer : Didin
      Status : Create
    */
    public static function getCode($row = 0, $column = 0, $level = 0) {
        $r = [null, null, null, null];
        $r[0] = chr(self::$startRow + $row - 1);
        $r[1] = chr(self::$startColumn + $column - 1);
        $r[2] = self::$separatorLevel;
        $r[3] = $level;

        $res = implode('', $r);
        return $res;
    }

    /*
      Date : 29-08-2021
      Description : Generate warehouse map
      Developer : Didin
      Status : Create
    */
    public static function generate($warehouse_id, $row = 0, $column = 0, $level = 0) {
        if(!$row) {
            throw new Exception('Row is required');
        }
        if(!$column) {
            throw new Exception('Column is required');
        }
        if(!$level) {
            throw new Exception('Level is required');
        }
        Warehouse::updateMap($warehouse_id, $row, $column, $level);
        for($x = 1;$x <= $row;$x++) {        
            for($y = 1;$y <= $column;$y++) {
                for($z = 1;$z <= $level;$z++) {
                    $params = [];
                    $params['row'] = $x;
                    $params['column'] = $y;
                    $params['level'] = $z;
                    $params['warehouse_id'] = $warehouse_id;
                    $params['code'] = self::getCode($x, $y, $z);
                    self::store($params, $warehouse_id);
                }
            }
        }

        self::clear($warehouse_id);
    }

    /*
      Date : 29-08-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('warehouse_maps');

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
        $dt = self::query();
        $dt = $dt->where('warehouse_maps.id', $id);
        $dt = $dt->select('warehouse_maps.*');
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
        $dt = DB::table('warehouse_maps')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Picking detail not found');
        }
    }

    /*
      Date : 23-03-2021
      Description : Mengambil parameter untuk input data
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['row'] = $args['row'] ?? null;
        $params['column'] = $args['column'] ?? null;
        $params['level'] = $args['level'] ?? null;
        $params['code'] = $args['code'] ?? null;

        self::validateFetchProcess($args);

        return $params;
    }

    /*
      Date : 23-03-2021
      Description : Validasi parameter input data
      Developer : Didin
      Status : Create
    */
    public static function validateFetchProcess($args = []) {
        $request = new Request($args);
        try {
            $request->validate([
                'row' => 'required',
                'column' => 'required',
                'level' => 'required',
                'row' => 'integer',
                'column' => 'integer',
                'level' => 'integer',
                'code' => 'required'
            ]);
        } catch(Exception $e) {
            throw new Exception($e->getMessage);
        }
    }

    /*
      Date : 23-03-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params = [], $warehouse_id) {
        $id = null;
        $params = self::fetch($params);
        $params['warehouse_id'] = $warehouse_id;
        $exist = self::validateIsExist($params['warehouse_id'], $params['row'], $params['column'], $params['level']);
        if(!$exist) {
            $params['created_at'] = Carbon::now();
            $id = DB::table('warehouse_maps')
            ->insertGetId($params);
        }

        return $id;
    }

    /*
      Date : 23-03-2021
      Description : Mencari tahu apakah sudah pernah ada map terkait
      Developer : Didin
      Status : Create
    */
    public static function validateIsExist($warehouse_id, $row, $column, $level) {
        $dt = self::query();
        $dt = $dt->where('warehouse_maps.warehouse_id', $warehouse_id);
        $dt = $dt->where('warehouse_maps.row', $row);
        $dt = $dt->where('warehouse_maps.column', $column);
        $dt = $dt->where('warehouse_maps.level', $level);

        $exist = $dt->count('warehouse_maps.id');
        $res = false;
        if($exist > 0) {
            $res = true;
        }

        return $res;
    }

    /*
      Date : 23-03-2021
      Description : Menghapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table('rack_maps')->where('rack_maps.warehouse_map_id')->delete();
        self::query()->where('warehouse_maps.id', $id)->delete();        
    }

    /*
      Date : 23-03-2021
      Description : Menghapus map yang tidak diperlukan
      Developer : Didin
      Status : Create
    */
    public static function clear($warehouse_id) {
        $wh = Warehouse::show($warehouse_id);
        $dt = self::query();
        $dt = $dt->where('warehouse_maps.warehouse_id', $warehouse_id);
        $dt = $dt->where(function($query) use ($wh){
            $query = $query->where('warehouse_maps.row', '>', $wh->row);
            $query = $query->orWhere('warehouse_maps.column', '>', $wh->column);
            $query = $query->orWhere('warehouse_maps.level', '>', $wh->level);
        });
        $dt = $dt->select('warehouse_maps.id')->get();
        foreach($dt as $d) {
            self::destroy($d->id);
        }
    }
}
