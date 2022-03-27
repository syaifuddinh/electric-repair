<?php

namespace App\Abstracts\Inventory;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;

class Warehouse
{
    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table('warehouses');

        return $dt;
    }

    /*
      Date : 05-03-2021
      Description : Menampilkan daftar nama item condition
      Developer : Didin
      Status : Create
    */
    public static function index() {
        $dt = self::query();
        $dt = $dt->select('warehouses.id', 'warehouses.name');
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
        $params['name'] = $args['name'] ?? null;
        $params['description'] = $args['description'] ?? null;
        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('warehouses')
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Warehouse not found');
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
        $dt = $dt->leftJoin('companies as c','c.id','warehouses.company_id');
        $dt = $dt->leftJoin('cities as cy','cy.id','warehouses.city_id');
        $dt = $dt->leftJoin('warehouse_types as wt','wt.id','warehouses.warehouse_type_id');
        $dt = $dt->where('warehouses.id', $id);
        $dt = $dt->selectRaw('warehouses.*, c.name as company, cy.name as city, wt.name as wh_type');
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
        DB::table('warehouses')->insert($insert);
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
        DB::table('warehouses')
        ->whereId($id)
        ->update($update);
    }
    
    /*
      Date : 29-08-2021
      Description : Update data map 
      Developer : Didin
      Status : Create
    */
    public static function updateMap($id, $row, $column, $level) {
        self::validate($id);
        DB::table('warehouses')
        ->whereId($id)
        ->update([
            'row' => $row,
            'column' => $column,
            'level' => $level
        ]);
    }

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table('warehouses')
        ->whereId($id)
        ->delete();
    }
}
