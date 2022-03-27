<?php

namespace App\Http\Controllers\Api\v4\Vehicle;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Response;
use DB;

class VehicleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $v = new \App\Http\Controllers\Vehicle\VehicleController();
        $dt = $v->index();

        return $dt;
    }
}
