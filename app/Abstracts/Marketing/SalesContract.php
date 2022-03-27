<?php

namespace App\Abstracts\Marketing;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Marketing\Quotation;

class SalesContract extends Quotation
{

    /*
      Date : 08-06-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params = []) {
        $params['is_sales_contract'] = 1;
        return parent::store($params);
    }
}
