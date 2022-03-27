<?php
namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Marketing\SalesContract;
use DB;

class SalesContractController extends Controller
{

    /*
      Date : 08-06-2021
      Description : Menyimpan data
      Developer : Didin
      Status : Create
    */
    public function store(Request $request) {
        // dd($request);
        $request->validate([
            'name' => 'required',
            'bill_type' => 'required',
            'customer_stage_id' => 'required',
            'customer_id' => 'required',
            'send_type' => 'required',
            'date_inquery' => 'required',
            'price_full_inquery' => 'required_if:bill_type,2',
            'imposition' => 'required_if:bill_type,2',
            'piece_id' => 'required_if:imposition,3'
        ]);

        $status_code = 200;
        $msg = 'Data successfully saved';
        DB::beginTransaction();
        try {
            $params = $request->all();
            $params['created_by'] = auth()->id();
            $params['company_id'] = auth()->user()->company_id;
            SalesContract::store($params);
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
