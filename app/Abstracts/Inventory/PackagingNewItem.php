<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Inventory\Packaging;

class PackagingNewItem
{
    /*
      Date : 05-03-2021
      Description : Menghapus semua data
      Developer : Didin
      Status : Create
    */
    public static function clear($packaging_id) {
        Packaging::validate($packaging_id);
        $dt = self::query();
        $dt = $dt->where('packaging_new_items.packaging_id', $packaging_id);
        $dt->delete();
    }

    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('packaging_new_items');
        $dt = $dt->join('items', 'items.id', 'packaging_new_items.item_id');

        return $dt;
    }
    /*
      Date : 05-03-2021
      Description : Menampilkan daftar item pada inspeksi
      Developer : Didin
      Status : Create
    */
    public static function index($packaging_id) {
        Packaging::validate($packaging_id);
        $dt = self::query();
        $dt = $dt->where('packaging_new_items.packaging_id', $packaging_id);
        $dt = $dt->select('packaging_new_items.id','packaging_new_items.item_id', 'packaging_new_items.qty', 'packaging_new_items.packaging_id', 'items.name AS item_name');
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
        $params['item_id'] = $args['item_id'] ?? null;
        $params['qty'] = $args['qty'] ?? 0;
        $params['updated_at'] = Carbon::now();
        if(!$params['item_id']) {
            throw new Exception('Item is required');
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
        $dt = DB::table('packaging_new_items')
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
        $dt = $dt->where('packaging_new_items.id', $id);
        $dt = $dt->select('packaging_new_items.id', 'packaging_new_items.date', 'packaging_new_items.description','packaging_new_items.checker_id', 'contacts.name AS checker_name');
        $dt = $dt->first();

        return $dt;
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function storeMultiple($details, $packaging_id) {
        if(is_array($details)) {
            self::clear($packaging_id);
            foreach($details as $detail) {
                $detail = (array) $detail;
                self::store($detail, $packaging_id);
            }
        }
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params, $packaging_id) {
        $insert = self::fetch($params);
        $insert['created_at'] = Carbon::now();
        $insert['packaging_id'] = $packaging_id;
        DB::table('packaging_new_items')->insert($insert);
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
        DB::table('packaging_new_items')
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
        DB::table('packaging_new_items')
        ->whereId($id)
        ->delete();
    }
}
