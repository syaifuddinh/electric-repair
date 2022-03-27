<?php

namespace App\Http\Controllers\Api\v4\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\PurchaseOrder;
use DB;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
{
  public function index(Request $request)
  {
    $dt = PurchaseOrder::index($request->keyword);
    $data['message'] = 'OK';
    $data['data'] = $dt;
    return response()->json($data);
  }
}
