<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Inventory\Warehouse;

class RackMap
{
    /*
      Date : 29-08-2021
      Description : Meng-query data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('rack_maps');
        $dt = $dt->leftJoin('warehouse_maps', 'warehouse_maps.id', 'rack_maps.warehouse_map_id');

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi apakah rak ini sudah pernah sudah punya  bin location / rack atau belum
      Developer : Didin
      Status : Create
    */  
    public static function validate($rack_id) {
        $res = false;
        $exist = self::query()
        ->where('rack_maps.rack_id', $rack_id)
        ->count('rack_maps.id');

        if($exist > 0) {
            $res = true;
        }

        return $res;
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi apakah rak ini sudah pernah sudah punya  bin location / rack atau belum
      Developer : Didin
      Status : Create
    */  
    public static function validateMap($warehouse_map_id) {
        $res = false;
        $exist = self::query()
        ->where('rack_maps.warehouse_map_id', $warehouse_map_id)
        ->count('rack_maps.id');

        if($exist > 0) {
            $res = true;
        }

        return $res;
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi apakah map ini sudah pernah ditempatkan pada 1 bin location / rack
      Developer : Didin
      Status : Create
    */  
    public static function validateIsExist($rack_id, $warehouse_map_id) {
        $res = false;
        $exist = self::query()
        ->where('rack_maps.rack_id', '!=', $rack_id)
        ->where('rack_maps.warehouse_map_id', '=', $warehouse_map_id)
        ->count('rack_maps.id');        

        if($exist > 0) {
            throw new Exception('This map has been used in another bin location');
        }
    }

    /*
      Date : 29-08-2021
      Description : Memvalidasi apakah rak ini sudah pernah digunakan pada map lain
      Developer : Didin
      Status : Create
    */  
    public static function validateIsUsed($rack_id) {
        $exist = self::query()
        ->where('rack_maps.rack_id', $rack_id)
        ->count('rack_maps.id');
        if($exist) {
            throw new Exception('This bin location has been used in another map');
        }
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan map
      Developer : Didin
      Status : Create
    */   
    public static function store($rack_id, $warehouse_map_id) {
        if($warehouse_map_id) {
            $exist = self::validateIsExist($rack_id, $warehouse_map_id);
            $exist = self::validate($rack_id);
            if($exist) {
                DB::table('rack_maps')
                ->whereRackId($rack_id)
                ->update([
                    'warehouse_map_id' => $warehouse_map_id,
                    'updated_at' => Carbon::now()
                ]);
            } else {
                DB::table('rack_maps')
                ->insert([
                    'rack_id' => $rack_id,
                    'warehouse_map_id' => $warehouse_map_id,
                    'created_at' => Carbon::now()
                ]);

            }
        }
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan map
      Developer : Didin
      Status : Create
    */   
    public static function assign($rack_id, $warehouse_map_id) {
        if($warehouse_map_id) {
            self::validateIsUsed($rack_id);
            $exist = self::validateMap($warehouse_map_id);
            if($exist) {
                DB::table('rack_maps')
                ->whereWarehouseMapId($warehouse_map_id)
                ->update([
                    'rack_id' => $rack_id,
                    'updated_at' => Carbon::now()
                ]);
            } else {
                DB::table('rack_maps')
                ->insert([
                    'rack_id' => $rack_id,
                    'warehouse_map_id' => $warehouse_map_id,
                    'created_at' => Carbon::now()
                ]);

            }
        }
    }

    public static function showByRack($rack_id) {
        $dt = self::query();
        $dt = $dt->where('rack_maps.rack_id', $rack_id);
        $dt = $dt->select('rack_maps.warehouse_map_id', 'warehouse_maps.code AS warehouse_map_code');
        $dt = $dt->first();

        if(!$dt) {
            $dt = '{"warehouse_map_id" : null, "warehouse_map_code" : null}';
            $dt = json_decode($dt);
        }

        return $dt;
    }
}
