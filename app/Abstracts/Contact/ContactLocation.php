<?php

namespace App\Abstracts\Contact;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Setting\Math;

class ContactLocation
{
    protected static $table = 'contact_locations';

    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query($params = []) {
        $dt = DB::table(self::$table);

        $params = self::fetchFilter($params);

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
        $dt = $dt->get();

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter untuk filter
      Developer : Didin
      Status : Create
    */
    public static function fetchFilter($args = []) {
        $params = [];
        return $params;
    }
    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['contact_id'] = $args['contact_id'] ?? null;
        $params['latitude'] = $args['latitude'] ?? null;
        $params['longitude'] = $args['longitude'] ?? null;

        if(!$params['contact_id']) {
            throw new Exception('Contact is required');
        }

        if(!$params['latitude']) {
            throw new Exception('Latitude is required');
        }

        if(!$params['longitude']) {
            throw new Exception('Longitude is required');
        }

        return $params;
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
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail kategori barang
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        // self::validate($id);
        // $dt = self::query();
        // $dt = $dt->where(self::$table . '.id', $id);
        // $dt = $dt->select(
        //     self::$table . '.*', 
        //     'warehouses.name AS warehouse_name',
        //     'companies.name AS company_name',
        //     'suppliers.name AS supplier_name',
        //     'creators.name AS creator_name'
        // );
        // $dt = $dt->first();

        // return $dt;
    }

    /*
      Date : 03-08-2021
      Description : Menampilkan tracking lokasi per driver
      Developer : Hendra
      Status : Create
    */
    public static function showHistory($contactId)
    {
        $dt = self::query();
        $dt = $dt->where('contact_id', $contactId);
        $dt = $dt->get();

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
        $id = DB::table(self::$table)->insertGetId($insert);

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
        DB::table('warehouses')
        ->whereId($id)
        ->update($update);
    }
    
    /*
      Date : 29-08-2021
      Description : Update data map 
      Developer : Didin
      Status : Create
    */
    public static function updateMap($id, $row, $column, $level) {
        self::validate($id);
        DB::table('warehouses')
        ->whereId($id)
        ->update([
            'row' => $row,
            'column' => $column,
            'level' => $level
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
        DB::table('warehouses')
        ->whereId($id)
        ->delete();
    }

    

    public static function getDistance($start_time = null, $end_time = null, $contact_id) {
        $dt = DB::table(self::$table);
        if($start_time) {
            $dt = $dt->where(self::$table . '.created_at', '>=', $start_time);
        }

        if($end_time) {
            $dt = $dt->where(self::$table . '.created_at', '<=', $end_time);
        }

        $dt = $dt->orderBy(self::$table . '.created_at', 'ASC');
        $dt = $dt->where(self::$table . '.contact_id', $contact_id);
        $dt = $dt->get();
        $r = 0;
        foreach($dt as $i => $unit) {
            if($i < count($dt) - 1) {
                $lat1 = $unit->latitude;
                $lon1 = $unit->longitude;
                $lat2 = $dt[$i + 1]->latitude;
                $lon2 = $dt[$i + 1]->longitude;
                $distance = Math::getDistance($lat1, $lon1, $lat2, $lon2);
                $r += $distance;
            }
        }

        return $r;
    }
}
