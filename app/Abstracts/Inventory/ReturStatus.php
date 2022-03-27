<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;

class ReturStatus
{
    public static function getDraft() {
        return 1;
    }

    public static function getApproved() {
        return 2;
    }

}
