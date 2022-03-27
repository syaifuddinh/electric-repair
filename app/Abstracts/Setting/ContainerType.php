<?php

namespace App\Abstracts\Setting;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class ContainerType 
{
    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('container_types');

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
        $dt = $dt->select('container_types.id', DB::raw('CONCAT(size, " ", unit, " - ",  container_types.name) AS `name`'));
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
        $dt = DB::table('container_types')
        ->whereId($id)
        ->first();

        return $dt;
    }

    
    
    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('container_types')
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
        DB::table('container_types')->insert($insert);
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
        DB::table('container_types')
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
        $params['code'] = $args['code'] ?? null;
        $params['name'] = $args['name'] ?? null;
        $params['size'] = $args['size'] ?? null;
        $params['unit'] = $args['unit'] ?? null;
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
        $r = new Request($params);
        $r->validate([
            'name' => 'required',
            'code' => 'required'
        ]);
    }

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table('container_types')
        ->whereId($id)
        ->delete();
    }
}
