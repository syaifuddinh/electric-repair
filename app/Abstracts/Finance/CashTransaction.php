<?php

namespace App\Abstracts\Finance;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Finance\CashTransactionCostStatus;

class CashTransaction 
{
    protected static $table = 'cash_transactions';

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

    public static function validateWasFinished($id) {
        $dt = self::show($id);
        $status_cost = CashTransactionCostStatus::getFinishedStatus();

        if($dt->status_cost == $status_cost) {
            throw new Exception('Data sudah di-approve');
        }
    }

    public static function validateWasRequested($id) {
        $dt = self::show($id);
        $status_cost = CashTransactionCostStatus::getDraftStatus();

        if($dt->status_cost == $status_cost) {
            throw new Exception('Data sudah diajukan');
        }
    }
}
