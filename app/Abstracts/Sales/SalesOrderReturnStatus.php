<?php

namespace App\Abstracts\Sales;

use DB;
use Carbon\Carbon;
use Exception;

class SalesOrderReturnStatus 
{
    protected static $table = 'sales_order_return_statuses';
    public static function getFinishedStatus() {
        $r = null;
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.slug', 'finished');
        $dt = $dt->first();
        if($dt) {
            $r = $dt->id;
        }

        return $r;
    }
}
