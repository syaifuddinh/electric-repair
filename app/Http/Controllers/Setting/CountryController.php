<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use DB;
use Exception;

class CountryController extends Controller
{
    /*
      Date : 02-12-2020
      Description : Menampilkan daftar negara
      Developer : Didin
      Status : Create
    */
    public function index()
    {
        $dt = DB::table('countries')
        ->select('id', 'name')
        ->get();

        return response()->json($dt);
    }
}
        