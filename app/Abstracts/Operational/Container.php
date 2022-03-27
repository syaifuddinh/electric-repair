<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Operational\JobOrderContainer;
use App\Abstracts\JobOrder;

class Container
{
    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('containers')
        ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 07-03-2021
      Description : Menghapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        $jo_container = JobOrderContainer::showByContainer($id);
        if($jo_container) {
            $job_order_id = $jo_container->job_order_id;
        }
        DB::table('containers')
        ->whereId($id)
        ->delete();
        JobOrder::countPrice($job_order_id);
    }
}
