<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;

class ReceiptQualityStatus
{
    protected static $table = 'receipt_quality_statuses';

    public static function getDraft() {
        $dt = self::showBySlug('isDraft');
        $r = $dt->id ?? null;
        return $r;
    }

    public static function getApproved() {
        $dt = self::showBySlug('isApproved');
        $r = $dt->id ?? null;
        return $r;
    }

    public static function getRejected() {
        $dt = self::showBySlug('isRejected');
        $r = $dt->id ?? null;
        return $r;
    }

    public static function showBySlug($slug) {
        $dt = DB::table(self::$table);
        $dt = $dt->whereSlug($slug);
        $dt = $dt->first();

        return $dt;
    }

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
      Description : Menampilkan detail
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
}
