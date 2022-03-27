<?php

namespace App\Abstracts\Operational;

use DB;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Operational\Manifest;

class Crossdocking
{
    /*
      Date : 12-02-2021
      Description : Menampilkan detail stuffing
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        $dt = Manifest::show($id, null, 1);

        return $dt;
    }

    /*
      Date : 12-02-2021
      Description : Menghapus stuffing
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        Manifest::validate($id, null, 1);
        $dt = Manifest::destroy($id);
    }

    /*
      Date : 12-02-2021
      Description : Meng-update stuffing
      Developer : Didin
      Status : Create
    */
    public static function update($args = [], $id) {
        Manifest::validate($id, null, 1);
        $dt = Manifest::update($args, $id);
    }
}
