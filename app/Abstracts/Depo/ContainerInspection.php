<?php

namespace App\Abstracts\Depo;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;
use App\Abstracts\Depo\ContainerInspectionDetail;
use App\Abstracts\Setting\Checker;

class ContainerInspection 
{
    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('container_inspections');
        $dt = $dt->leftJoin('contacts', 'contacts.id', 'checker_id');

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Mengambil parameter
      Developer : Didin
      Status : Create
    */
    public static function fetch($args) {
        $params = [];
        $params['created_by'] = $args['created_by'] ?? auth()->id();
        $params['description'] = $args['description'] ?? null;
        $params['checker_id'] = $args['checker_id'] ?? null;
        $params['date'] = $args['date'] ?? null;
        if($params['date']) {
            Checker::checkDate($params['date']);
            $params['date'] = Carbon::parse($params['date'])->format('Y-m-d');
        }
        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('container_inspections')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Item condition not found');
        }
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail kategori barang
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = self::query();
        $dt = $dt->where('container_inspections.id', $id);
        $dt = $dt->select('container_inspections.id', 'container_inspections.date', 'container_inspections.description','container_inspections.checker_id', 'contacts.name AS checker_name');
        $dt = $dt->first();

        return $dt;
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params) {
        $insert = self::fetch($params);
        $id = DB::table('container_inspections')
        ->insertGetId($insert);

        return $id;
    }
    
    /*
      Date : 29-08-2021
      Description : Update data
      Developer : Didin
      Status : Create
    */
    public static function update($params, $id) {
        self::validate($id);
        $update = self::fetch($params);
        DB::table('container_inspections')
        ->whereId($id)
        ->update($update);
    }
    
    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        ContainerInspectionDetail::clear($id);
        DB::table('container_inspections')
        ->whereId($id)
        ->delete();
    }
}
