<?php

namespace App\Abstracts\Finance;

use DB;
use Carbon\Carbon;
use Exception;

class Closing 
{
    protected static $table = 'closing';

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
        self::validate($id);
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Mencegah perubahan data berdasarkan tanggal
      Developer : Didin
      Status : Create
    */
    public static function preventByDate($date) {
        $dt = DB::table(self::$table);
        $dt = $dt->where('start_periode' , '<=', $date);
        $dt = $dt->where('end_periode' , '>=', $date);
        $dt = $dt->where('status' , 1);
        $dt = $dt->where('is_lock' , 1);
        $dt = $dt->first();
        if($dt) {
            $dateDesc = fullDate($dt->start_periode); 
            $msg = 'Transaction was locked in ' . $dateDesc;
            throw new Exception($msg);
        }
    }

    /*
      Date : 05-03-2021
      Description : Membatalkan closing
      Developer : Didin
      Status : Create
    */
    public static function rollback($id) {
        $dt = self::show($id);
        if($dt->status == 0) {
            throw new Exception('Data was unclosed');
        }

        DB::table(self::$table)->whereId($id)->update([
            'status' => 0
        ]);
    }
}
