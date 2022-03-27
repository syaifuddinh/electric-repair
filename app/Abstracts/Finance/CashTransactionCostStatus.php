<?php

namespace App\Abstracts\Finance;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;

class CashTransactionCostStatus
{
    protected static $table = 'cash_transaction_cost_statuses';

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
        $dt = $dt->whereId($id);
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }

    public static function showBySlug($slug) {
        $dt = DB::table(self::$table)
        ->whereSlug($slug)
        ->first();

        return $dt;
    }

    public static function getDraftStatus() {
        $r = null;
        $dt =  self::showBySlug('draft');
        if($dt) {
            $r = $dt->id;
        }

        return $r;
    }

    public static function getFinishedStatus() {
        $r = null;
        $dt =  self::showBySlug('finished');
        if($dt) {
            $r = $dt->id;
        }

        return $r;
    }
}
