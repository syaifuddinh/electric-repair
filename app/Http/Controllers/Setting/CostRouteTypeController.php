<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Setting\Operational\CostRouteType;
use DB;
use Exception;

class CostRouteTypeController extends Controller
{

    /*
      Date : 05-03-2020
      Description : Menampilkan data
      Developer : Didin
      Status : Edit
    */
    public function index(Request $request)
    {
        $data['message'] = 'OK';
        $dt = CostRouteType::index();
        $data['data'] = $dt;

        return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /*
      Date : 05-03-2020
      Description : Menampilkan detail
      Developer : Didin
      Status : Edit
    */
    public function show($id)
    {
        $data['message'] = 'OK';
        $dt = CostRouteType::show($id);
        $data['data'] = $dt;

        return response()->json($data, 200, [], JSON_NUMERIC_CHECK);
    }
}
