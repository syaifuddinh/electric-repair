<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;

class ItemCategory 
{
    protected static $table = 'categories';

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $type_ban = $args['type_ban'] ?? 0;
        $params['parent_id'] = $args['parent_id'] ?? null;
        $params['code'] = $args['code'] ?? null;
        $params['name'] = $args['name'] ?? null;
        $params['is_container_part'] = $args['is_container_part'] ?? 0;
        $params['is_container_yard'] = $args['is_container_yard'] ?? 0;
        $params['is_asset'] = $args['is_asset'] ?? 0;
        $params['is_jasa'] = $args['is_jasa'] ?? 0;
        $params['is_tire'] = $args['is_tire'] ?? 0;
        $params['is_pallet'] = $args['is_pallet'] ?? 0;
        $params['is_ban_luar'] = ($type_ban==1?1:0);
        $params['is_ban_dalam'] = ($type_ban==2?1:0);
        $params['is_marset'] = ($type_ban==3?1:0);
        $params['description'] = $args['description'] ?? null;
        $params['default_rack_id'] = $args['default_rack_id'] ?? null;
        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('categories')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Item category not found');
        }
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail kategori barang
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table . ' as c');
        $dt = $dt->leftJoin(self::$table . ' as pr','pr.id','c.parent_id');
        $dt = $dt->leftJoin('racks', 'racks.id', 'c.default_rack_id');
        $dt = $dt->select('c.*', 'pr.name as parent', 'racks.code AS rack_code');
        $dt = $dt->where('c.id', $id)->first();

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
        DB::table('categories')->insert($insert);
    }
    
    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params, $id) {
        self::validate($id);
        $update = self::fetch($params);
        DB::table('categories')
        ->whereId($id)
        ->update($update);
    }

    /*
      Date : 29-08-2021
      Description : Mendapatkan default pallet category
      Developer : Didin
      Status : Create
    */
    public static function getPallet() {
        $dt = DB::table(self::$table)->whereIsPallet(1)->whereNotNull("parent_id");
        $dt = $dt->first();
        if(!$dt) {
            throw new Exception('Pallet category is not set, please set pallet category in master category');
        }

        return $dt->id;
    }
}
