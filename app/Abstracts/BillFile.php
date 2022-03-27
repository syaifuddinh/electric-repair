<?php

namespace App\Abstracts;

use DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use File;

class BillFile
{
    /*
      Date : 29-08-2020
      Description : Menyimpan file bukti pembayaran
      Developer : Didin
      Status : Create
    */
    public static function store($file, $bill_id) {
        $ext = $file->getClientOriginalExtension(); 
        $name = $file->getClientOriginalName();
        $filename = $name;
        $uniq_name = Carbon::now()->format('YmdHis') . round(rand() * 100) . '.' . $ext;
        $file->move(public_path('files/bill'), $uniq_name);
        $params = [];
        $params['bill_id'] = $bill_id;
        $params['name'] = $filename;
        $params['filename'] = $uniq_name;
        $params['created_at'] = Carbon::now();
        DB::table('bill_files')->insert($params);
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan daftar file
      Developer : Didin
      Status : Create
    */
    public static function index($bill_id) {
        $url = asset('files/bill') . '/';
        $dt = DB::table('bill_files')
        ->where('bill_id', $bill_id)
        ->select('bill_files.id', 'bill_files.name', DB::raw("CONCAT('$url', bill_files.filename) AS filename"))
        ->get();

        return $dt;
    }

    /*
      Date : 29-08-2020
      Description : Menghapus file
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        $dt = DB::table('bill_files')
        ->whereId($id)
        ->first();
        if(!$dt) {
            throw new Exception('Data not found');
        }   
        $filename = public_path('files/bill') . '/' . $dt->filename;
        if(file_exists($filename)) {
            File::delete($filename);
        }
        DB::table('bill_files')
        ->whereId($id)
        ->delete();
    }
}
