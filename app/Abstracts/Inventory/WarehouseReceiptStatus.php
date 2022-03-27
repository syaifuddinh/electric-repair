<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;

class WarehouseReceiptStatus
{
    public static function getDraft() {
        return 0;
    }

    public static function getApproved() {
        return 1;
    }


    public static function getRejected() {
        return 2;
    }

}
