<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Setting\Setting;
use App\Abstracts\Setting\Math;
use Illuminate\Support\Str;

class ItemType 
{
    /*
      Date : 29-08-2021
      Description : Mendapatkan item type yang bernilai "both"
      Developer : Didin
      Status : Create
    */
    public static function getBoth() {
        return 3;
    }
}
