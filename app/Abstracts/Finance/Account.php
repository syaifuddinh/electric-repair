<?php

namespace App\Abstracts\Finance;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Utils\TransactionCode;

class Account 
{
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
        $dt = DB::table('accounts')
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
        $data = DB::table('accounts');
        $data = $data->leftJoin('categories as c','c.id','accounts.category_id');
        $data = $data->leftJoin('pieces as p','p.id','accounts.piece_id');
        $data = $data->leftJoin('accounts as ac_item','ac_item.id','accounts.account_id');
        $data = $data->leftJoin('accounts as ac_payable','ac_payable.id','accounts.account_payable_id');
        $data = $data->leftJoin('accounts as ac_cash','ac_cash.id','accounts.account_cash_id');
        $data = $data->leftJoin('accounts as ac_cost','ac_cost.id','accounts.account_purchase_id');
        $data = $data->leftJoin('accounts as ac_sale','ac_sale.id','accounts.account_sale_id');
        $data = $data->leftJoin('contacts as v','v.id','accounts.main_supplier_id');
        $data = $data->selectRaw('
        accounts.*,
        c.name as category,
        p.name as piece,
        concat(ac_item.code,\'-\',ac_item.name) as account_item,
        concat(ac_payable.code,\'-\',ac_payable.name) as account_payable,
        concat(ac_cash.code,\'-\',ac_cash.name) as account_cash,
        concat(ac_cost.code,\'-\',ac_cost.name) as account_cost,
        concat(ac_sale.code,\'-\',ac_sale.name) as account_sale,
        v.name as vendor
        ');
        $data = $data->where('accounts.id', $id);
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
      Description : Memvalidasi barang, apakah sudah tercatat pada stok atau belum
      Developer : Didin
      Status : Create
    */
    public static function validateInStock($id) {
        $exist = DB::table('stock_transactions')
        ->whereItemId($id)
        ->count('id');

        if($exist > 0) {
            throw new Exception('This item has transaction');
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
