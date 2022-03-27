<?php

namespace App\Abstracts\Depo;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Depo\ContainerInspection;

class ContainerInspectionDetail
{
    /*
      Date : 05-03-2021
      Description : Menghapus semua data
      Developer : Didin
      Status : Create
    */
    public static function clear($container_inspection_id) {
        ContainerInspection::validate($container_inspection_id);
        $dt = self::query();
        $dt = $dt->where('container_inspection_details.container_inspection_id', $container_inspection_id);
        $dt->delete();
    }/*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('container_inspection_details');
        $dt = $dt->join('items', 'items.id', 'container_inspection_details.container_part_id');
        $dt = $dt->join('item_conditions', 'item_conditions.id', 'container_inspection_details.item_condition_id');

        return $dt;
    }
    /*
      Date : 05-03-2021
      Description : Menampilkan daftar item pada inspeksi
      Developer : Didin
      Status : Create
    */
    public static function index($container_inspection_id) {
        ContainerInspection::validate($container_inspection_id);
        $dt = self::query();
        $dt = $dt->where('container_inspection_details.container_inspection_id', $container_inspection_id);
        $dt = $dt->select('container_inspection_details.id', 'container_inspection_details.container_part_id', 'container_inspection_details.item_condition_id', 'items.name AS item_name', 'item_conditions.name AS item_condition_name');
        $dt = $dt->get();

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
        $params['container_part_id'] = $args['container_part_id'] ?? null;
        $params['item_condition_id'] = $args['item_condition_id'] ?? null;
        $params['updated_at'] = Carbon::now();
        if(!$params['container_part_id']) {
            throw new Exception('Container part is required');
        }
        if(!$params['item_condition_id']) {
            throw new Exception('Item condition is required');
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
        $dt = DB::table('container_inspection_details')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Container detail not found');
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
        $dt = $dt->where('container_inspection_details.id', $id);
        $dt = $dt->select('container_inspection_details.id', 'container_inspection_details.date', 'container_inspection_details.description','container_inspection_details.checker_id', 'contacts.name AS checker_name');
        $dt = $dt->first();

        return $dt;
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function storeMultiple($details, $container_inspection_id) {
        if(is_array($details)) {
            self::clear($container_inspection_id);
            foreach($details as $detail) {
                $detail = (array) $detail;
                self::store($detail, $container_inspection_id);
            }
        }
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params, $container_inspection_id) {
        $insert = self::fetch($params);
        $insert['created_at'] = Carbon::now();
        $insert['container_inspection_id'] = $container_inspection_id;
        DB::table('container_inspection_details')->insert($insert);
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
        DB::table('container_inspection_details')
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
        self::validate($id);
        DB::table('container_inspection_details')
        ->whereId($id)
        ->delete();
    }
}
