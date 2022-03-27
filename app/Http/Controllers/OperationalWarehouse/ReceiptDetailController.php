<?php

namespace App\Http\Controllers\OperationalWarehouse;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use DB;
use App\Abstracts\Inventory\WarehouseReceiptDetail;

class ReceiptDetailController extends Controller
{
    /*
      Date : 10-03-2020
      Description : Approve data
      Developer : Didin
      Status : Edit
    */
    public function approveQuality($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            WarehouseReceiptDetail::approveQuality($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }

    /*
      Date : 10-03-2020
      Description : Reject data
      Developer : Didin
      Status : Edit
    */
    public function rejectQuality($id)
    {
        $status_code = 200;
        $msg = 'Data successfully updated';
        DB::beginTransaction();
        try {
            WarehouseReceiptDetail::rejectQuality($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $status_code = 421;
            $msg = $e->getMessage();
        }
        $data['message'] = $msg;

        return response()->json($data, $status_code);
    }
}
