<?php

namespace App\Http\Controllers\Api\v4\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use App\Abstracts\ReceiptType;
use DB;
use Response;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Exception;

class ReceiptTypeController extends Controller
{
    /*
      Date : 24-03-2020
      Description : Menampilkan tipe penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function index()
    { 
        $data['message'] = 'OK';
        $data['data'] = ReceiptType::index();

        return response()->json($data);
    }

    /*
      Date : 24-03-2020
      Description : Menampilkan tipe penerimaan barang
      Developer : Didin
      Status : Create
    */
    public function show($id)
    { 
        $data['message'] = 'OK';
        $status_code = 200;
        try {
            $data['data'] = ReceiptType::show($id);
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421; 
        }

        return response()->json($data, $status_code);
    }
    
    /*
      Date : 18-05-2020
      Description : Menampilkan tipe penerimaan barang berdasarkan kode
      Developer : Didin
      Status : Create
    */
    public function showBySlug($slug)
    { 
        $data['message'] = 'OK';
        $status_code = 200;
        try {
            $data['data'] = ReceiptType::showByCode($slug);
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421; 
        }

        return response()->json($data, $status_code);
    }

}
