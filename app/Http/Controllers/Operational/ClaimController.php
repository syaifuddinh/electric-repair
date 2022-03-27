<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Utils\TransactionCode;
use Carbon\Carbon;
use Exception;
use App\Abstracts\Journal;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ClaimController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $val = [
            'date_transaction' => 'required',
            'company_id' => 'required',
            'customer_id' => 'required',
            'detail' => 'required',
            'claim_subject' => 'required|in:1,2'
        ];
        $message = [
            'date_transaction.required' => 'Tanggal klaim tidak boleh kosong',
            'company_id.required' => 'Cabang tidak boleh kosong',
            'customer_id.required' => 'Customer tidak boleh kosong',
            'detail.required' => 'Detail tidak boleh kosong',
            'claim_subject.required' => 'Pilihan klaim wajib diisi',
            'claim_subject.in' => 'Pilihan klaim tidak valid'
        ];

        if($request->claim_subject == 1){
            $val['job_order_id'] = 'required|exists:job_orders,id';
            $message['job_order_id.required'] = 'Job order tidak boleh kosong';
            $message['job_order_id.exists'] = 'Job order tidak valid';
            
        } else if($request->claim_subject == 2){
            $val['sales_order_id'] = 'required|exists:sales_orders,id';
            $message['sales_order_id.required'] = 'Sales order tidak boleh kosong';
            $message['sales_order_id.exists'] = 'Sales order tidak valid';
        }

        if($request->claim_type == 1) {
            $val['driver_id'] = 'required';
            $message['driver_id.required'] = 'Driver tidak boleh kosong';
        } else if($request->claim_type == 2) {
            $val['vendor_id'] = 'required';
            $message['vendor_id.required'] = 'Vendor tidak boleh kosong';
        }
        $request->validate($val, $message);

        DB::beginTransaction();
        try {
            $required = ['job_order_id', 'sales_order_id', 'date_transaction', 'customer_id', 'company_id', 'driver_id', 'vendor_id', 'claim_type', 'description'];
            $params = $request->only($required);
            $params['date_transaction'] = dateDB($request->date_transaction);

            if(!empty($request->job_order_id)){
                $jobOrder = DB::table('job_orders')
                                ->whereid($request->job_order_id)
                                ->first();
            } else if (!empty($request->sales_order_id)){
                $salesOrder = DB::table('sales_orders')
                                ->whereId($request->sales_order_id)
                                ->first();
                $jobOrder = DB::table('job_orders')
                                ->whereid($salesOrder->job_order_id)
                                ->first();
            }

            $code = new TransactionCode($request->company_id, 'claim');
            $code->setCode();
            $trx_code = $code->getCode();

            $params['code'] = $trx_code;
            $params['collectible_id'] = $jobOrder->collectible_id ?? $jobOrder->receiver_id;

            $claim_id = DB::table('claims')->insertGetId($params);

            $this->storeDetails($request->detail, $claim_id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 421);
        }

        $data['message'] = 'Data berhasil disimpan';

        return response()->json($data, 200);
    }

    public function storeDetails($details, $claim_id)
    {
        foreach(collect($details) as $d){
            $d = (object) $d;
            $detail = [];
            $detail['header_id'] = $claim_id;
            $detail['description'] = $d->desription ?? null;
            // if($d->type == 2){
            //     $detail['job_order_detail_id'] = null;
            // } else {
            //     $detail['job_order_detail_id'] = $d->job_order_detail_id;
            // }
            $detail['qty'] = $d->qty;
            $detail['price'] = $d->price;
            $detail['total_price'] = $d->total_price;
            $detail['claim_qty'] = $d->claim_qty;
            $detail['claim_price'] = $d->claim_price;
            $detail['claim_total_price'] = $d->claim_total_price;
            $detail['commodity_id'] = $d->commodity_id;

            $claim_detail_id = DB::table('claim_details')->insertGetId($detail);

            $claim_categories = $d->claim_categories ?? $d->causes ?? [];
            $claimCategoriesData = [];
            foreach($claim_categories as $cCat){
                if(isset($cCat['value']) && $cCat['value'] == true){
                    $cat = [];
                    $cat['claim_detail_id'] = $claim_detail_id;
                    $cat['category_id'] = $cCat['id'];
                    $cat['created_at'] = Carbon::now();

                    $claimCategoriesData[] = $cat;
                } else if(isset($cCat['claim_category_id']) && !empty($cCat['claim_category_id'])){
                    $cat = [];
                    $cat['claim_detail_id'] = $claim_detail_id;
                    $cat['category_id'] = $cCat['claim_category_id'];
                    $cat['created_at'] = Carbon::now();

                    $claimCategoriesData[] = $cat;
                }
            }

            DB::table('claim_category_details')->insert($claimCategoriesData);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $exist = DB::table('claims')
                        ->whereId($id)
                        ->count('id');
            if($exist == 0) {
                throw new Exception('Data tidak ditemukan');
            }
            $dt = DB::table('claims')
                ->join('job_orders', 'job_orders.id', 'claims.job_order_id')
                ->join('companies', 'companies.id', 'claims.company_id')
                ->join('contacts AS customers', 'customers.id', 'claims.customer_id')
                ->leftJoin('contacts AS vendors', 'vendors.id', 'claims.vendor_id')
                ->leftJoin('contacts AS drivers', 'drivers.id', 'claims.driver_id')
                ->where('claims.id', $id)
                ->select(
                    'claims.id', 
                    'claims.date_transaction', 
                    'claims.journal_id', 
                    'job_orders.code AS job_order_code',
                    'job_orders.shipment_date AS job_order_date',
                    'companies.name AS company_name', 
                    'customers.name AS customer_name', 
                    'vendors.name AS vendor_name', 
                    'drivers.name AS driver_name', 
                    'claims.status',
                    DB::raw('IF(claims.status = 1, "Draft", "Disetujui") AS status_name'), 
                    'claims.customer_id',
                    'claims.job_order_id', 
                    'claims.company_id', 
                    'claims.claim_type', 
                    DB::raw('IF(claims.claim_type = 1, "Driver", "Vendor" ) AS claim_type_name'), 
                    'claims.driver_id', 
                    'claims.vendor_id', 
                    'claims.description')
                ->first();
        } catch(\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 421);
        }

        return response()->json($dt);
    }

    public function showDetail($id)
    {
        $claimCategoryDetail= DB::raw("(SELECT claim_detail_id, JSON_ARRAYAGG(JSON_OBJECT('claim_category_name', claim_categories.name, 'claim_category_id', claim_categories.id)) AS causes FROM `claim_category_details` JOIN claim_categories ON claim_categories.id = claim_category_details.category_id GROUP BY claim_detail_id) AS claim_category_details");

        $dt = DB::table('claim_details')
                ->join('commodities', 'commodities.id', 'claim_details.commodity_id')
                ->leftJoin($claimCategoryDetail, 'claim_category_details.claim_detail_id', 'claim_details.id')
                ->where('claim_details.header_id', $id)
                ->select('claim_details.id', 'commodities.name AS commodity_name', 'claim_details.commodity_id', 'claim_details.qty', 'claim_details.price', 'claim_details.total_price', 'claim_details.claim_total_price', 'claim_details.claim_qty', 
                    'claim_details.claim_price', 
                    'claim_details.description',
                    DB::raw("COALESCE(claim_category_details.causes, '[]') AS causes")    
                )
                ->get();

        $dt = $dt->map(function($d){
            $d->causes = json_decode($d->causes);
            return $d;
        });
        
        return response()->json($dt);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $val = [
            'date_transaction' => 'required',
            'company_id' => 'required',
            'customer_id' => 'required',
            'detail' => 'required',
            'claim_subject' => 'required|in:1,2'
        ];
        $message = [
            'date_transaction.required' => 'Tanggal klaim tidak boleh kosong',
            'company_id.required' => 'Cabang tidak boleh kosong',
            'customer_id.required' => 'Customer tidak boleh kosong',
            'detail.required' => 'Detail tidak boleh kosong',
            'claim_subject.required' => 'Pilihan klaim wajib diisi',
            'claim_subject.in' => 'Pilihan klaim tidak valid'
        ];
        if($request->claim_subject == 1){
            $val['job_order_id'] = 'required';
            $message['job_order_id.required'] = 'Job order tidak boleh kosong';
        } else if($request->claim_subject == 2){
            $val['sales_order_id'] = 'required';
            $message['sales_order_id.required'] = 'Sales order tidak boleh kosong';
        }
        if($request->claim_type == 1) {
            $val['driver_id'] = 'required';
            $message['driver_id.required'] = 'Driver tidak boleh kosong';
        } else if($request->claim_type == 2) {
            $val['vendor_id'] = 'required';
            $message['vendor_id.required'] = 'Vendor tidak boleh kosong';
        }
        $request->validate($val, $message);

        DB::beginTransaction();
        try {
            $required = ['job_order_id', 'sales_order_id', 'date_transaction', 'customer_id', 'company_id', 'driver_id', 'vendor_id', 'claim_type', 'description'];
            $params = $request->only($required);
            $params['date_transaction'] = dateDB($request->date_transaction);

            if(!empty($request->job_order_id)){
                $jobOrder = DB::table('job_orders')
                                ->whereid($request->job_order_id)
                                ->first();
            } else if (!empty($request->sales_order_id)){
                $salesOrder = DB::table('sales_orders')
                                ->whereId($request->sales_order_id)
                                ->first();
                $jobOrder = DB::table('job_orders')
                                ->whereid($salesOrder->job_order_id)
                                ->first();
            }

            $params['collectible_id'] = $jobOrder->collectible_id ?? $jobOrder->receiver_id;

            DB::table('claims')->whereId($id)->update($params);

            DB::table('claim_details')
                    ->whereHeaderId($id)
                    ->delete();
                    
            $this->storeDetails($request->detail, $id);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 421);
        }

        $data['message'] = 'Data berhasil disimpan';

        return response()->json($data, 200);
    }

    public function approve($id)
    {
        $exist = DB::table('claims')
                    ->whereId($id)
                    ->count('id');

        DB::beginTransaction();
        try {
            if($exist > 0) {
                $claim = DB::table('claims')
                        ->whereId($id)
                        ->first();
                if($claim->status == 1) {
                    DB::table('claims')
                        ->whereId($id)
                        ->update([
                            'status' => 2
                        ]);
                    Journal::setJournal(122, $id);
                } else {
                    throw new Exception('Data yang sudah disetujui tidak bisa disetujui lagi');
                }
                DB::commit();
            } else {
                throw new Exception ('Data tidak ditemukan');
            }
        } catch(Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 421);
        }

        return response()->json(['message' => 'Data berhasil disetujui']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $exist = DB::table('claims')
        ->whereId($id)
        ->count('id');

        DB::beginTransaction();
        try {
            if($exist > 0) {
                DB::table('claim_details')
                    ->whereHeaderId($id)
                    ->delete();
                DB::table('claims')
                    ->whereId($id)
                    ->delete();
                DB::commit();
            } else {
                throw new Exception ('Data tidak ditemukan');
            }
        } catch(Exception $e) {
            DB::rollback();
            return response()->json(['message' => $e->getMessage()], 421);
        }

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}
