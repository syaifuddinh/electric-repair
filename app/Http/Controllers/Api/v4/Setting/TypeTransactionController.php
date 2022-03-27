<?php

namespace App\Http\Controllers\Api\v4\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Setting\TypeTransaction;
use Response;
use DB;

class TypeTransactionController extends Controller
{
    public function show($id)
    {
        $dt = TypeTransaction::show($id);
        $data['message'] = 'OK';
        $data['data'] = $dt;

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
}
