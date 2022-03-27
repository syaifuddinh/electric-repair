<?php

namespace App\Abstracts\Depo;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Depo\MovementContainer;
use App\Abstracts\Setting\Checker;

class MovementContainerDetail
{
    protected static $table = 'movement_container_details';

    /*
      Date : 05-03-2021
      Description : Menghapus semua data
      Developer : Didin
      Status : Create
    */
    public static function clear($movement_container_id) {
        MovementContainer::validate($movement_container_id);
        $dt = self::query();
        $dt = $dt->where('movement_container_details.movement_container_id', $movement_container_id);
        $dt->delete();
    }
    
    /*
      Date : 05-03-2021
      Description : Mengquery data
      Developer : Didin
      Status : Create
    */
    public static function query() {
        $dt = DB::table(self::$table);
        $dt = $dt->join('gate_in_containers', self::$table . '.gate_in_container_id', 'gate_in_containers.id');
        $dt = $dt->join('items', self::$table . '.container_yard_destination_id', 'items.id');

        return $dt;
    }
    /*
      Date : 05-03-2021
      Description : Menampilkan daftar item pada inspeksi
      Developer : Didin
      Status : Create
    */
    public static function index($movement_container_id) {
        MovementContainer::validate($movement_container_id);
        $dt = self::query();
        $dt = $dt->where('movement_container_details.movement_container_id', $movement_container_id);
        $dt = $dt->select(self::$table . '.id', self::$table . '.gate_in_container_id', self::$table . '.container_yard_destination_id', 'gate_in_containers.code AS gate_in_container_code', 'items.name AS container_yard_destination_code', 'gate_in_containers.no_container' );
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
        $params['container_yard_destination_id'] = $args['container_yard_destination_id'] ?? null;
        $params['gate_in_container_id'] = $args['gate_in_container_id'] ?? null;
        $params['updated_at'] = Carbon::now();
        self::validateInput($params);

        return $params;
    }

    /*
      Date : 05-03-2021
      Description : Validasi input data
      Developer : Didin
      Status : Create
    */
    public static function validateInput($params) {
        $r = new Request($params);
        $r->validate([
            'gate_in_container_id' => 'required',
            'container_yard_destination_id' => 'required'
        ]);
        Checker::checkItem($params['container_yard_destination_id']);
        Checker::checkGateInContainer($params['gate_in_container_id']);
    }

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table('movement_container_details')
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
        $dt = $dt->where('movement_container_details.id', $id);
        $dt = $dt->select('movement_container_details.id', 'movement_container_details.date', 'movement_container_details.description','movement_container_details.checker_id', 'contacts.name AS checker_name');
        $dt = $dt->first();

        return $dt;
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function storeMultiple($details, $movement_container_id) {
        if(is_array($details)) {
            self::clear($movement_container_id);
            foreach($details as $detail) {
                $detail = (array) $detail;
                self::store($detail, $movement_container_id);
            }
        }
    }
    
    /*
      Date : 29-08-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public static function store($params, $movement_container_id) {
        $insert = self::fetch($params);
        $insert['created_at'] = Carbon::now();
        $insert['movement_container_id'] = $movement_container_id;
        DB::table(self::$table)->insert($insert);
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
        DB::table(self::$table)
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
        DB::table(self::$table)
        ->whereId($id)
        ->delete();
    }
}
