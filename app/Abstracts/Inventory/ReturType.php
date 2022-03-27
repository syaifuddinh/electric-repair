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

class ReturType 
{
    public static function getItem() {
        return 1;
    }

    public static function getCash() {
        return 2;
    }

}
