<?php

namespace App\Http\Controllers\Sales;

use App\Abstracts\Sales\CustomerOrder;
use App\Abstracts\Sales\CustomerOrderDetail;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\ContactFile;
use App\Model\CustomerOrder as ModelCustomerOrder;
use App\Model\CustomerOrderDetail as ModelCustomerOrderDetail;
use App\Model\CustomerOrderFile;
use App\Model\Quotation;
use App\Model\SalesOrder;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class CustomerOrderController extends Controller
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
     * Date : 07-07-2021
     * Description : Menyimpan data customer order
     * Developer : Hendra
     * Status : Create
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(!auth()->user()->hasRole('sales.customer_order.add')){
            throw new Exception('Anda tidak memiliki hak akses ini');
        }

        $request->validate([
            'customer_id' => 'required|exists:contacts,id',
            'code' => 'nullable',
            'quotation_id' => 'nullable|exists:quotations,id',
            'date' => 'required|date',
            'description' => 'nullable|string',
            'payment' => 'required|in:1,2',
            'detail' => 'required',
            'detail.*.item_id' => 'required|exists:items,id',
            'detail.*.qty' => 'required|numeric',
            'detail.*.stock' => 'required|numeric',
            'detail.*.price' => 'required|numeric',
            'files' => 'nullable',
        ], [
            'customer_id.required'=> 'Customer tidak boleh kosong',
            'quotation_id.exists'=> 'Contract tidak valid',
            'date.required'=> 'Tanggal tidak boleh kosong',
            'date.date'=> 'Tanggal tidak valid',
            'payment.required'=> 'Pembayaran tidak boleh kosong',
            'payment.in'=> 'Pembayaran yang dipilih tidak valid',
            // 'files.required'=> 'File tidak boleh kosong',
        ]);

        if(is_string($request->detail)){
            $validator = Validator::make(json_decode($request->detail), [
                'detail.*.item_id' => 'required|exists:items,id',
                'detail.*.qty' => 'required|numeric',
                'detail.*.stock' => 'required|numeric',
                'detail.*.price' => 'required|numeric',
            ]);

            if($validator->fails()){
                throw new Exception($validator->errors()->first());
            }
            
            if(empty(json_decode($request->detail))){
                throw new Exception("Harap isi item detail");
            }
        }
        
        $q = Quotation::find($request->quotation_id);
        if($q && $q->customer_id != $request->customer_id){
            throw new Exception('Kontrak dan Customer tidak sesuai');
        }
        
        $status = DB::table('customer_order_statuses')->get();
        $statusApproved = $status->where('slug', 'approved')->first();
        $statusDraft = $status->where('slug', 'draft')->first();
        if($status->isEmpty() || empty($statusApproved) || empty($statusDraft)){
            throw new Exception('Status Customer Order Tidak Ditemukan');
        }

        $data['message'] = 'Data successfully saved';
        $status_code = 200;
        $now = Carbon::now();
        DB::beginTransaction();
        try {
            $params = [];
            $params['customer_id'] = $request->customer_id;
            $params['code'] = $request->code;
            $params['quotation_id'] = $request->quotation_id ?? null;
            $params['date'] = dateDB($request->date);
            $params['description'] = $request->description;
            $params['payment_type'] = $request->payment;
            $params['customer_order_status_id'] = $statusDraft->id;
            $params['created_by'] = auth()->user()->id;
            $params['created_at'] = $now;
            $params['updated_at'] = $now;
            
            $customer_order_id = DB::table('customer_orders')
            ->insertGetId($params);
            
            $details = [];
            if(is_string($request->detail)) {
                $detail = json_decode($request->detail);
            } else {
                $detail = $request->detail; 
            }
            foreach($detail as $x){
                $dataDetail = [];
                $dataDetail['header_id'] = $customer_order_id;
                $dataDetail['item_id'] = $x->item_id;
                $dataDetail['qty'] = $x->qty;
                $dataDetail['stock'] = $x->stock;
                $dataDetail['price'] = $x->price;
                $dataDetail['warehouse_receipt_detail_id'] = $x->warehouse_receipt_detail_id;
                $dataDetail['rack_id'] = $x->rack_id;
                $dataDetail['description'] = $x->description;
                $dataDetail['created_at'] = $now;
                $dataDetail['updated_at'] = $now;
                
                $details[] = $dataDetail;
            }

            $co_details = DB::table('customer_order_details')->insert($details);

            if($request->has('files')){
                $files=$request->file('files');
                $c = 0;
                foreach($files as $f){
                    $filename="CUSTOMER-ORDER_".$customer_order_id."_".date('Ymd_His').'_'.$c.'.'.$f->getClientOriginalExtension();
                    $fname="CUSTOMER-ORDER_".$customer_order_id."_".date('Ymd_His').'_'.$c;
    
                    $f->move(public_path('files'), $filename);
    
                    CustomerOrderFile::create([
                        'header_id' => $customer_order_id,
                        'name' => $fname,
                        'file_name' => 'files/'.$filename,
                        'date_upload' => date('Y-m-d'),
                        'extension' => $f->getClientOriginalExtension()
                    ]);
                    $c++;
                }
            }


            $data['data'] = ['id' => $customer_order_id];
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return response()->json($data, $status_code);
    }

    /**
     * Display the specified resource.
     *
     * Date : 07-07-2021
     * Description : Mendapatkan data customer order
     * Developer : Hendra
     * Status : Create
     *  
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data['message'] = 'OK';
        $status_code = 200;
        try {
            $dt = CustomerOrder::show($id);
            if(!$dt) {
                throw new Exception('Data not found');
            }
            $data['data'] = $dt;
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return response()->json($data, $status_code);
    }

    /**
     * Display the specified resource details.
     *
     * Date : 08-07-2021
     * Description : Mendapatkan data customer order details
     * Developer : Hendra
     * Status : Create
     *  
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showDetail($id)
    {
        $data['message'] = 'OK';
        $status_code = 200;
        try {
            $dt = CustomerOrderDetail::index($id);
            if(!$dt) {
                throw new Exception('Data not found');
            }
            $data['data'] = $dt;
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return response()->json($data, $status_code);
    }
    
    /**
     * Display the specified resource details.
     *
     * Date : 08-07-2021
     * Description : Mendapatkan data file customer order
     * Developer : Hendra
     * Status : Create
     *  
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showFile($id)
    {
        $data['message'] = 'OK';
        $status_code = 200;
        try {
            $dt = CustomerOrderFile::where('header_id',$id)->get();
            $data['data'] = $dt;
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return response()->json($data, $status_code);
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
     * Date : 08-07-2021
     * Description : Mengupdate customer order
     * Developer : Hendra
     * Status : Create
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(!auth()->user()->hasRole('sales.customer_order.edit')){
            throw new Exception('Anda tidak memiliki hak akses ini');
        }
        $request->validate([
            'detail' => 'required',
            'detail.*.item_id' => 'required|exists:items,id',
            'detail.*.qty' => 'required|numeric',
            'detail.*.stock' => 'required|numeric',
            'detail.*.price' => 'required|numeric',
        ],[
            'detail.required' => 'Detail item tidak boleh kosong',
        ]);

        if(is_string($request->detail)){
            $validator = Validator::make(json_decode($request->detail), [
                'detail.*.item_id' => 'required|exists:items,id',
                'detail.*.qty' => 'required|numeric',
                'detail.*.stock' => 'required|numeric',
                'detail.*.price' => 'required|numeric',
            ]);

            if($validator->fails()){
                throw new Exception($validator->errors()->first());
            }
            
            if(empty(json_decode($request->detail))){
                throw new Exception("Harap isi item detail");
            }
        }

        $q = Quotation::find($request->quotation_id);
        if($q && $q->customer_id != $request->customer_id){
            throw new Exception('Kontrak dan Customer tidak sesuai');
        }

        $data['message'] = 'Data successfully updated';
        $status_code = 200;
        $now = Carbon::now();
        DB::beginTransaction();
        try {
            $params = [];
            $params['code'] = $request->code;
            $params['description'] = $request->description;
            
            ModelCustomerOrder::find($id)->update($params);

            $customer_order_id = DB::table('customer_orders')
            ->where('id', $id)
            ->first();

            $detailItems = collect($request->detail);
            $allCoDetails = ModelCustomerOrderDetail::where('header_id', $id)->get();
            foreach($allCoDetails as $x){
                if($detailItems->isNotEmpty()){
                    $oldDetail = $allCoDetails->shift();
                    $newDetail = $detailItems->shift();
                    
                    $oldDetail->item_id = $newDetail['item_id'];
                    $oldDetail->price = $newDetail['price'];
                    $oldDetail->qty = $newDetail['qty'];
                    $oldDetail->stock = $newDetail['stock'];
                    $oldDetail->warehouse_receipt_detail_id = $newDetail['warehouse_receipt_detail_id'];
                    $oldDetail->rack_id = $newDetail['rack_id'];
                    $oldDetail->description = $newDetail['description'];
                    $oldDetail->save();
                }
            }

            if($detailItems->isNotEmpty()){
                foreach($detailItems as $item){
                    $newDt = [];
                    $newDt['header_id'] = $id;
                    $newDt['item_id'] = $item['item_id'];
                    $newDt['price'] = $item['price'];
                    $newDt['qty'] = $item['qty'];
                    $newDt['stock'] = $item['stock'];
                    $newDt['warehouse_receipt_detail_id'] = $x->warehouse_receipt_detail_id;
                    $newDt['rack_id'] = $x->rack_id;
                    $newDt['description'] = $item['description'];
                    $newDt['updated_at'] = $now;
                    DB::table('customer_order_details')->insert($newDt);
                }
            }
    
            if($allCoDetails->isNotEmpty()){
                foreach($allCoDetails as $del){
                    $del->delete();
                }
            }

            $data['data'] = $customer_order_id;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return response()->json($data, $status_code);
    }

    /**
     * Remove the specified resource from storage.
     *
     * Date : 08-07-2021
     * Description : Menghapus customer order
     * Developer : Hendra
     * Status : Create
     * 
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(!auth()->user()->hasRole('sales.customer_order.delete')){
            throw new Exception('Anda tidak memiliki hak akses ini');
        }

        $data['message'] = 'Data successfully deleted';
        $status_code = 200;
        $so = SalesOrder::where('customer_order_id', $id)->count();
        if($so > 0){
            throw new Exception('Customer Order sudah memiliki Sales Order');
        }
        DB::beginTransaction();
        try {
            CustomerOrder::destroy($id);
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            $data['message'] = $e->getMessage();
            $status_code = 421;
        }

        return response()->json($data, $status_code);
    }

    /**
     *
     * Date : 08-07-2021
     * Description : Ubah status Customer Order menjadi approved lalu generate Sales Order
     * Developer : Hendra
     * Status : Create
     * 
     */
    public function approve(Request $request, $id)
    {
        if(!auth()->user()->hasRole('sales.customer_order.approve')){
            throw new Exception('Anda tidak memiliki hak akses ini');
        }

        CustomerOrder::validate($id);

        $statusApprove = CustomerOrder::isApprovedOrRejected($id);
        if($statusApprove['status'] == true){
            throw new Exception('Customer Order sudah dalam status ' . $statusApprove['status_name']);
        }
        
        $data['message'] = 'Data successfully approved';
        $status_code = 200;
        $status = DB::table('customer_order_statuses')->where('slug', 'approved')->first();
        if($status){
            CustomerOrder::generateSo($id);
            $data['data'] = [
                'status_slug' => $status->slug, 
                'status' => $status->name
            ];
        } else {
            throw new Exception('Status approve customer order tidak ditemukan');
        }

        return response()->json($data, $status_code);
    }

    /**
     *
     * Date : 08-07-2021
     * Description : Ubah status Customer Order menjadi rejected
     * Developer : Hendra
     * Status : Create
     * 
     */
    public function reject(Request $request, $id)
    {
        if(!auth()->user()->hasRole('sales.customer_order.approve')){
            throw new Exception('Anda tidak memiliki hak akses ini');
        }

        $data['message'] = 'Data successfully rejected';
        $status_code = 200;
        CustomerOrder::validate($id);

        $statusApprove = CustomerOrder::isApprovedOrRejected($id);
        if($statusApprove['status'] == true){
            throw new Exception('Customer Order sudah dalam status ' . $statusApprove['status_name']);
        }

        $status = DB::table('customer_order_statuses')->where('slug', 'rejected')->first();
        if($status){
            $co = DB::table('customer_orders')
                        ->where('id', $id)
                        ->update([
                            'customer_order_status_id' => $status->id
                        ]);
            $data['data'] = [
                'status_slug' => $status->slug, 
                'status' => $status->name
            ];
        } else {
            throw new Exception('Status reject customer order tidak ditemukan');
        }

        return response()->json($data, $status_code);
    }

    /**
     *
     * Date : 08-07-2021
     * Description : Upload File untuk Customer Order
     * Developer : Hendra
     * Status : Create
     * 
     */
    public function uploadFile(Request $request, $id)
    {
        if(!auth()->user()->hasRole('sales.customer_order.edit')){
            throw new Exception('Anda tidak memiliki hak akses ini');
        }
        $request->validate([
            'file' => 'required',
            'name' => 'required|unique:customer_order_files,name'
        ]);
        $file=$request->file('file');
        $filename="CUSTOMER-ORDER_".$id."_".date('Ymd_His').'.'.$file->getClientOriginalExtension();

        $file->move(public_path('files'), $filename);

        DB::beginTransaction();
        CustomerOrderFile::create([
            'header_id' => $id,
            'name' => $request->name,
            'file_name' => 'files/'.$filename,
            'date_upload' => date('Y-m-d'),
            'extension' => $file->getClientOriginalExtension()
        ]);
        DB::commit();

        return response()->json(null);
    }

    /**
     *
     * Date : 08-07-2021
     * Description : Hapus File Customer Order
     * Developer : Hendra
     * Status : Create
     * 
     */
    public function deleteFile($id)
    {
        if(!auth()->user()->hasRole('sales.customer_order.delete')){
            throw new Exception('Anda tidak memiliki hak akses ini');
        }
        DB::beginTransaction();
        $cf = CustomerOrderFile::find($id);
        if($cf){
            $f = File::delete(public_path().'/'.$cf->file_name);
            if ($f) {
                $cf->delete();
            }
            DB::commit();
        } else {
            throw new Exception('File tidak ditemukan');
        }
        $data['message'] = 'Data Berhasil Dihapus';

        return response()->json($data, 200);
    }
}
