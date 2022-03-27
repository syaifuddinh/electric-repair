<?php

namespace App\Abstracts\Marketing;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Setting\Checker;

class Inquery
{
    protected static $table = 'inqueries';

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

    /*
      Date : 12-02-2021
      Description : Manangkap parameter untuk input data 
      Developer : Didin
      Status : Create
    */
    public static function fetch($args = []) {
    }

    /*
      Date : 08-06-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params = []) {
    }

    /*
      Date : 12-02-2021
      Description : Realisasi inquery ke quotation
      Developer : Didin
      Status : Create
    */
    /*
      Date : 15-07-2021
      Description : add static method
      Developer : Hendra
      Status : Edit
    */
    public static function release($date_quotation, $quotation_id, $id) {
        $dt = self::show($id);
        DB::table(self::$table)->update([
            "status" => 4,
            "quotation_id" => $quotation_id,
            "date_quotation" => dateDB($date_quotation)
        ]);    

        if($dt->lead_id) {
            Quotation::setLead($dt->lead_id, $quotation_id);
            DB::table('leads')->whereId($dt->lead_id)->update([
                "step" => 4
            ]);
        }
    }
}
