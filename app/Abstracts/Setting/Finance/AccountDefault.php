<?php

namespace App\Abstracts\Setting\Finance;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class AccountDefault 
{
    protected static $table = 'account_defaults';

    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Didin
      Status : Create
    */
    public static function show() {
        $dt = DB::table(self::$table)->first();
        if(!$dt) {
            throw new Exception('Account default is empty');
        }

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Validasi apakah akun saldo awal sudah di set atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateAccountSaldoAwal() {
        $account = self::show();
        if(!$account->saldo_awal) {
            throw new Exception('Akun Saldo Awal belum di set pada master akun default');
        }
    }
}
