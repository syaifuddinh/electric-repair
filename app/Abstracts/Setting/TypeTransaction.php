<?php

namespace App\Abstracts\Setting;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class TypeTransaction 
{
    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        $dt = DB::table('type_transactions')
        ->whereId($id)
        ->first();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Validasi keberadaan data berdasarkan slug
      Developer : Didin
      Status : Create
    */
    public static function validateBySlug($slug) {
        $dt = DB::table('type_transactions')
        ->whereSlug($slug)
        ->first();

        if(!$dt) {
            throw new Exception('Type transaction not found');
        }
    }
    
    /*
      Date : 29-08-2020
      Description : Menampilkan Detail berdasarkan slug
      Developer : Didin
      Status : Create
    */
    public static function showBySlug($slug) {
        $dt = DB::table('type_transactions')
        ->whereSlug($slug)
        ->first();

        return $dt;
    }
}
