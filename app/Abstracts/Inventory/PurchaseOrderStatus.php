<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;

class PurchaseOrderStatus
{
    protected static $table = 'purchase_order_statuses';

    public static function query() {
        $dt = DB::table(self::$table);

        return $dt;
    }

    public static function index() {
        $dt = self::query()->get();

        return $dt;
    }

    public static function getRequested() {
        $r = null;
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.slug', 'requested');
        $dt = $dt->first();
        if($dt) {
            $r = $dt->id;
        }

        return $r;
    }

    public static function getApproved() {
        $r = null;
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.slug', 'approved');
        $dt = $dt->first();
        if($dt) {
            $r = $dt->id;
        }

        return $r;
    }

    public static function getFinished() {
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
