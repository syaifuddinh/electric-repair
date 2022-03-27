<?php

namespace App\Abstracts\Finance\Asset;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;

class AssetPurchase 
{
    protected static $table = 'asset_purchases';

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        if(!$id) {
            throw new Exception('Account is required');
        }
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Account not found');
        }
    }
/*
      Date : 05-03-2021
      Description : Mendapatkan akun piutang
      Developer : Didin
      Status : Create
    */
    public static function getReceivable() {
        $dt = DB::table('account_defaults')
        ->first();

        $id = $dt->piutang;
        self::validate($id);

        return $id;
    }



    /*
      Date : 29-08-2021
      Description : Menampilkan detail kategori barang
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $data = DB::table(self::$table);
        $data = $data->where(self::$table . '.id', $id);
        $data = $data->first();

        $dt = $data;

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
        $id = DB::table('accounts')->insertGetId($insert);

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
        DB::table('accounts')
        ->whereId($id)
        ->update($update);
    }

    /*
      Date : 14-03-2021
      Description : Mendapatkan nilai status disetujui
      Developer : Didin
      Status : Create
    */
    public static function getApproveStatus() {
        return 2;
    }


    /*
      Date : 14-03-2021
      Description : Memvalidasi data, apakah sudah disetujui atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateIsApprove($id) {
        $dt = self::show($id);

        if($dt->status == self::getApproveStatus()) {
            throw new Exception('Data sudah disetujui');
        }
    }

    /*
      Date : 14-03-2021
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        DB::table('accounts')
        ->whereId($id)
        ->delete();
    }
}
