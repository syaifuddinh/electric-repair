<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\FieldType;
use Response;
use DB;

class FieldTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $dt = FieldType::index();
        $data['message'] = 'OK';
        $data['data'] = $dt;

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
}
