<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class FieldType
{
    /*
      Date : 29-08-2021
      Description : Menampilkan tipe data
      Developer : Didin
      Status : Create
    */
    public static function index() {
        $dt = DB::table('field_types')
        ->select('id', 'name')
        ->get();

        return $dt;
    }
}
