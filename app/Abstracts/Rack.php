<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use PDF;
use App\Abstracts\Inventory\RackMap;
use App\Abstracts\Setting\Checker;

class Rack
{
    protected static $table = 'racks';

    /*
      Date : 29-08-2020
      Description : Generate barcode PDF
      Developer : Didin
      Status : Create
    */
    public static function showQRCodePDF($rack_id) {
        $data['url'] = url('') . '#!/warehousing/bin_location/' . $rack_id;
        $pdf = PDF::loadView('pdf.inventory.rack.qrcode', $data);
        return $pdf;
    }

    /*
      Date : 29-08-2021
      Description : Meng-query data
      Developer : Didin
      Status : Create
    */ 
    public static function query($params = []) {
        $dt = DB::table(self::$table);
        $dt = $dt->join('warehouses', 'warehouses.id', 'racks.warehouse_id');
        $dt = $dt->join('companies', 'companies.id', 'warehouses.company_id');
        $dt = $dt->join('storage_types', 'storage_types.id', 'racks.storage_type_id');

        $warehouse_id = $params['warehouse_id'] ?? null;
        if($warehouse_id) {
            $dt = $dt->where('racks.warehouse_id', $warehouse_id);
        }

        $is_picking_area = $params['is_picking_area'] ?? null;
        if($is_picking_area !== null ) {
            $dt = $dt->where('storage_types.is_picking_area', $is_picking_area);
        }

        $is_dangerous_good = $params['is_dangerous_good'] ?? null;
        if($is_dangerous_good !== null ) {
            $dt = $dt->where('racks.is_dangerous_good', $is_dangerous_good);
        }

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan daftar nama bin location
      Developer : Didin
      Status : Create
    */
    public static function index($params = []) {
        $dt = self::query($params);
        $dt = $dt->select('racks.id', 'racks.code');
        $dt = $dt->get();

        return $dt;
    }

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
            throw new Exception('Rack not found');
        }
    }

    /*
      Date : 29-08-2021
      Description : Menangkap parameter untuk input data
      Developer : Didin
      Status : Create
    */ 
    public static function fetch($args = []) {
        $params = [];
        $params['is_dangerous_good'] = $args['is_dangerous_good'] ?? 0;
        $params['is_fast_moving'] = $args['is_fast_moving'] ?? 0;
        $params['warehouse_id'] = $args['warehouse_id'] ?? null;
        $params['code'] = $args['code'] ?? null;
        $params['barcode'] = $args['barcode'] ?? null;
        $params['luas'] = $args['luas'] ?? 0 ;        
        $params['is_floor_stake'] = $args['is_floor_stake'] ?? 0;
        $params['storage_type_id'] = $args['storage_type_id'] ?? null;
        $params['capacity_volume'] = $args['capacity_volume'] ?? 0;        
        $params['capacity_tonase'] = $args['capacity_tonase'] ?? 0;        
        $params['max_pallet'] = $args['max_pallet'] ?? 0;    
        $params['is_dangerous_good'] = $args['is_dangerous_good'] ?? 0;    
        $params['warehouse_map_id'] = $args['warehouse_map_id'] ?? null;    

        return $params;
    }

    /*
      Date : 29-08-2021
      Description : Menampikan detail
      Developer : Didin
      Status : Create
    */ 
    public static function show($id) {
        self::validate($id);
        $dt = self::query();
        $dt = $dt->where('racks.id', $id);
        $dt = $dt->select('racks.*',DB::raw("racks.code as name"), DB::raw('racks.capacity_volume - racks.capacity_volume_used AS capacity_volume'), DB::raw('racks.capacity_tonase - racks.capacity_tonase_used AS capacity_tonase'), 'companies.name AS company', 'storage_types.name AS storage_type');
        $dt = $dt->first();
        $map = RackMap::showByRack($id);
        if($map) {
            $dt->warehouse_map_id = $map->warehouse_map_id;
            $dt->warehouse_map_code = $map->warehouse_map_code;
        }

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params) {
        $insert = self::fetch($params);
        self::validateCode($params['code'], $params['warehouse_id']);
        unset($insert['warehouse_map_id']);
        $id = DB::table('racks')->insertGetId($insert);
        if($insert['warehouse_map_id'] ?? null) {
            RackMap::store($id, $insert['warehouse_map_id']);
        }

        return $id;
    }

    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params = [], $id) {
        $insert = self::fetch($params);
        self::validateCode($params['code'], $params['warehouse_id'], $id);
        unset($insert['warehouse_map_id']);
        DB::table('racks')
        ->whereId($id)
        ->update($insert);
        RackMap::store($id, $params['warehouse_map_id']);
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi apakah kode sudah pernah digunakan
      Developer : Didin
      Status : Create
    */
    public static function validateCode($code, $warehouse_id, $id = null) {
        $dt = self::query();
        $dt = $dt->where('racks.code', $code);
        $dt = $dt->where('racks.warehouse_id', $warehouse_id);

        if($id) {
            $dt = $dt->where('racks.id', '!=', $id);
        }

        $exist = $dt->count('racks.id');
        if($exist > 0) {
            throw new Exception('Bin location / rack code has been used');
        }
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi apakah kode sudah pernah digunakan
      Developer : Didin
      Status : Create
    */
    public static function suggestionQuery($warehouse_id, $orderType = 'ASC') {
        Checker::checkOrderType($orderType);
        Checker::checkWarehouse($warehouse_id);
        $dt = self::query();
        $dt = $dt->where('racks.warehouse_id', $warehouse_id);
        $dt = $dt->join('rack_maps', 'rack_maps.rack_id', 'racks.id');
        $dt = $dt->join('warehouse_maps', 'warehouse_maps.id', 'rack_maps.warehouse_map_id');

        $dt = $dt->orderBy('warehouse_maps.row', $orderType);
        $dt = $dt->orderBy('warehouse_maps.column', $orderType);
        $dt = $dt->orderBy('warehouse_maps.level', $orderType);

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Mendapatkan suggestion bin location untuk penempatan barang
      Developer : Didin
      Status : Create
      Return : racks.id [integer]
    */
    public static function getSuggestion($warehouse_id, $orderType = 'ASC', $items = [[]]) {
        $id = null;
        if($warehouse_id) {
            $dt = self::suggestionQuery($warehouse_id, $orderType);
            $dt = $dt->select('racks.id');
            $dt = $dt->first();
            if($dt) {
                $id = $dt->id;
            }        
        }

        return $id;
    }

    /*
      Date : 29-08-2021
      Description : Meng-query data
      Developer : Didin
      Status : Create
    */ 
    public static function getPickingArea($warehouse_id) {
        $r = null;
        $params = [];
        $params['warehouse_id'] = $warehouse_id;
        $params['is_picking_area'] = 1;
        $dt = self::query($params)->select(self::$table . '.id')->first();
        if($dt) {
            $r = $dt->id;
        } else {
            $insert = [];
            $insert['warehouse_id'] = $warehouse_id;
            $insert['code'] = 'PICKINGAREA';
            $insert['description'] = '-';
            $insert['capacity_volume'] = 999999999;
            $insert['capacity_tonase'] = 999999999;
            $r = self::store($insert);
        }
        

        return $r;

    }

}
