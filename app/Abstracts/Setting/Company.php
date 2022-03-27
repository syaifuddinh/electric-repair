<?php

namespace App\Abstracts\Setting;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class Company 
{
    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        $dt = DB::table('companies')
        ->leftJoin('cities', 'cities.id', 'companies.city_id')
        ->leftJoin('provinces', 'provinces.id', 'cities.province_id')
        ->leftJoin('countries', 'countries.id', 'provinces.country_id')
        ->where('companies.id',$id)
        ->select('companies.*', 'cities.name as city', 'provinces.name as province', 'countries.name as country')
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
        $dt = DB::table('companies')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Company / branch not found');
        }
    }
}
