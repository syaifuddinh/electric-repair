<?php

namespace App\Http\Controllers\Setting;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use DB;
use Exception;

class ServiceController extends Controller
{
    public function showStatuses($service_id) {
        $dt = DB::table('kpi_statuses')
        ->whereServiceId($service_id)
        ->get();

        return Response::json(['message' => 'OK', 'data' => $dt]);
    }
}
        