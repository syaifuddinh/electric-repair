<?php

namespace App\Abstracts\Inventory;

use DB;
use Exception;

class ItemDeletionStatus
{
    public static function getDraft() {
        return 1;
    }

    public static function getApproved() {
        return 2;
    }

}
