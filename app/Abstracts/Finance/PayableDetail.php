<?php

namespace App\Abstracts\Finance;

use DB;
use Carbon\Carbon;
use Exception;

class PayableDetail 
{
    protected static $table = 'payable_details';

    public static function clear($payable_id) {
        DB::table(self::$table)
        ->wherePayableId($payable_id)
        ->delete();
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
}
