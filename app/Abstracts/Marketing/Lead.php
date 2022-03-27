<?php

namespace App\Abstracts\Marketing;

use DB;
use Carbon\Carbon;
use Exception;

class Lead
{
    protected static $table = 'leads';

    /*
      Date : 12-02-2021
      Description : Meng-query data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table(self::$table);

        return $dt;
    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Quotation detail not found');
        }
    }

    /*
      Date : 12-02-2021
      Description : Menampilkan detail 
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table)
        ->where(self::$table  . '.id', $id)
        ->first();

        return $dt;
    }
}
