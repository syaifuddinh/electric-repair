<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;

class ReceiptType
{
    protected static $table = 'receipt_types';

    /*
      Date : 29-08-2020
      Description : Menampilkan daftar tipe penerimaan barang
      Developer : Didin
      Status : Create
    */
    public static function index() {
        $dt = DB::table('receipt_types')
        ->select('id', 'name')
        ->get();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan detail 
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        $dt = DB::table('receipt_types')
        ->where('receipt_types.id', $id)
        ->first();

        return $dt;
    }

    public static function showByCode($code) {
        $dt = DB::table(self::$table)
        ->whereCode($code)
        ->first();

        return $dt;
    }
}
