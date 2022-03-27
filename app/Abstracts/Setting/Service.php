<?php

namespace App\Abstracts\Setting;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class Service 
{
    protected static $table = 'services';

    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table(self::$table);

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan daftar nama item condition
      Developer : Didin
      Status : Create
    */
    public static function index() {
        $dt = self::query();
        $dt = $dt->select('pieces.id', 'pieces.name');
        $dt = $dt->get();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan Detail berdasarkan nama
      Developer : Didin
      Status : Create
    */
    public static function showByName($value) {
        $value = strtolower($value);
        $dt = DB::table(self::$table)
        ->whereRaw("LOWER(`name`) = '$value'")
        ->first();

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validateByName($value) {
        $res = false;
        $value = strtolower($value);
        $dt = DB::table(self::$table)
        ->whereRaw("LOWER(`name`) = '$value'")
        ->count(self::$table . '.id');

        if($dt > 0) {
            $res = true;
        }

        return $res;
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
            throw new Exception('Company / branch not found');
        }
    }

    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params) {
        $insert = self::fetch($params);
        $id = DB::table('pieces')->insertGetId($insert);

        return $id;
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
        DB::table('pieces')
        ->whereId($id)
        ->update($update);
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['name'] = $args['name'] ?? null;
        self::validateInput($params);
        
        return $params;
    }


    /*
      Date : 14-03-2021
      Description : Validasi input
      Developer : Didin
      Status : Create
    */
    public static function validateInput($params) {
        if(!$params['name']) {
            throw new Exception('Piece / unit name is required');
        }
    }

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table('pieces')
        ->whereId($id)
        ->delete();
    }
}
