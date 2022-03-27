<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Setting;
use App\Abstracts\Setting\Checker;
use App\Abstracts\Inventory\Warehouse;
use App\Abstracts\Inventory\ItemMigrationDetail;

class ItemMigrationType 
{
    protected static $table = 'item_migration_types';

    public static function getPutaway() {
        $r = null;
        $dt = DB::table(self::$table)
        ->whereSlug("putaway")
        ->first();
        $r = $dt->id ?? null;

        return $r;
    }

    public static function getItemMigration() {
        $r = null;
        $dt = DB::table(self::$table)
        ->whereSlug("itemMigration")
        ->first();
        $r = $dt->id ?? null;

        return $r;
    }
}
