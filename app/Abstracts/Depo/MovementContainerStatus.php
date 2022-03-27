<?php

namespace App\Abstracts\Depo;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Checker;

class MovementContainerStatus 
{
    protected static $table = 'movement_container_statuses';
    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table(self::$table);

        return $dt;
    }

    public static function getDraft() {
        $dt = self::query()->where('order', 1)->first();
        if(!$dt) {
            throw new Exception('Draft status not found');
        }
        $r = $dt->id;

        return $r;
    }

    public static function getApproved() {
        $dt = self::query()->where('order', 2)->first();
        if(!$dt) {
            throw new Exception('Approved status not found');
        }
        $r = $dt->id;

        return $r;
    }
}
