<?php

namespace App\Http\Controllers\Finance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Response;
use Carbon\Carbon;

class PajakController extends Controller
{
    /*
      Date : 18-03-2020
      Description : Generate faktur pajak
      Developer : Didin
      Status : Edit
    */
    public function store(Request $request)
    {
        $request->validate([
            'jumlah' => 'required',
            'awal' => 'required',
            'akhir' => 'required',
            'start_date' => 'required',
            'expiry_date' => 'required'
        ], [
            'jumlah.required' => 'Jumlah karakter tidak boleh kosong',
            'awal.required' => 'Awal tidak boleh kosong',
            'akhir.required' => 'Akhir tidak boleh kosong',
            'start_date.required' => 'Tanggal mulai berlaku tidak boleh kosong',
            'expiry_date.required' => 'Tanggal kadaluarsa  tidak boleh kosong'
        ]);
        $prefix = $request->prefix ?? '';
        $suffix = $request->suffix ?? '';
        $jumlah = $request->jumlah;
        $awal = $request->awal;
        $akhir = $request->akhir;

        for ($i=$awal; $i <= $akhir; $i++) {
          $kode = $prefix . str_pad($i, $jumlah, 0, STR_PAD_LEFT) . $suffix;
          DB::table('tax_invoices')->insert([
            'code' => $kode,
            'expiry_date' => Carbon::parse($request->expiry_date)->format('Y-m-d'),
            'start_date' => Carbon::parse($request->start_date)->format('Y-m-d')
          ]);
        }
        return Response::json(['message' => 'Faktur pajak berhasil di-generate'], 200, [], JSON_NUMERIC_CHECK);
    }
}
