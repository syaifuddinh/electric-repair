<?php

namespace App\Abstracts\Sales;

use App\Abstracts\Sales\CustomerOrderDetail as AbstractCustomerOrderDetail;
use App\Http\Controllers\Sales\SalesOrderController;
use Carbon\Carbon;
use Exception;
use App\Model\CustomerOrder as ModelCustomerOrder;
use App\Model\CustomerOrderDetail;
use App\Model\CustomerOrderFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CustomerOrder
{
    protected static $table = 'customer_orders';

    /*
      Date : 29-08-2021
      Description : Menampilkan customer order
      Developer : Hendra
      Status : Create
    */
    public static function query($request = []) {
        $request = self::fetchFilter($request);

        $dt = DB::table(self::$table)
                ->leftJoin('quotations', 'quotations.id', self::$table.'.quotation_id')
                ->join('contacts', 'contacts.id', self::$table.'.customer_id')
                ->leftJoin('customer_order_statuses', self::$table.'.customer_order_status_id', 'customer_order_statuses.id');

        return $dt;
    }

    public static function fetchFilter($args = []) {
        $params = [];

        return $params;
    }

    /*
      Date : 12-02-2021
      Description : Memvalidasi keberadaan data
      Developer : Hendra
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
                ->whereId($id);
        
        $dt = $dt->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 29-08-2020
      Description : Menampilkan Detail
      Developer : Hendra
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = self::query()
                ->where(self::$table . '.id', $id)
                ->select(self::$table . '.id', self::$table . '.code', 'contacts.name AS customer_name', self::$table.'.date', self::$table.'.customer_id', 'quotations.no_contract', self::$table.'.payment_type', self::$table.'.quotation_id', self::$table.'.description', 'customer_order_statuses.name as status', 'customer_order_statuses.slug as status_slug')
                ->first();

        return $dt;
    }

    /*
      Date : 07-03-2021
      Description : Menghapus data
      Developer : Hendra
      Status : Create
    */
    public static function destroy($id) {
        self::validate($id);
        CustomerOrderDetail::where('header_id', $id)->delete();
        $files = CustomerOrderFile::where('header_id', $id)->get();
        foreach($files as $f){
          $path = public_path($f->file_name);

          if(File::exists($path)){
            File::delete($path);
          }
          $f->delete();
        }
        DB::table(self::$table)
            ->whereId($id)
            ->delete();
    }

    public static function isApprovedOrRejected($id)
    {
      $status = false;
      $statusRejected = DB::table('customer_order_statuses')->where('slug', 'rejected')->first();
      $statusApproved = DB::table('customer_order_statuses')->where('slug', 'approved')->first();

      $co = ModelCustomerOrder::find($id);

      if($co->customer_order_status_id == $statusApproved->id){
        $status = true;
        $status_name = $statusApproved->name;
      } else if($co->customer_order_status_id == $statusRejected->id) {
        $status = true;
        $status_name = $statusRejected->name;
      } else {
        $status_name = null;
      }

      $result = [
        'status' => $status,
        'status_name' => $status_name
      ];

      return $result;
    }

    /**
     *
     * Date : 06-08-2021
     * Description : Generate SO dari CO 
     * Developer : Hendra
     * Status : Create
     * 
     */
    public static function generateSo($id)
    {
      DB::beginTransaction();
      try {
          $status = DB::table('customer_order_statuses')->where('slug', 'approved')->first();
          DB::table('customer_orders')
                      ->where('id', $id)
                      ->update([
                          'customer_order_status_id' => $status->id,
                          'approved_by' => auth()->user()->id,
                      ]);
          $co = self::show($id);
          $cod = AbstractCustomerOrderDetail::index($id);
          $detail = [];
          foreach($cod as $x){
              $detail[] = [
                  'piece_id' => $x->piece_id,
                  'total_item'=> $x->qty, //qty
                  'item_name'=> $x->code ? ($x->code . ' - ' .$x->name) : $x->name,
                  'description'=> $x->description,
                  'item_id'=> $x->item_id,
                  'rack_id'=> $x->rack_id,
                  'warehouse_receipt_detail_id'=> $x->warehouse_receipt_detail_id,
                  'quotation_id'=> $co->quotation_id,
              ];
          }

          $req = new Request();
          $req->customer_order_id = $co->id;
          $req->customer_id = $co->customer_id;
          $req->wo_customer = $co->code;
          $req->quotation_id = $co->quotation_id;
          $req->description = $co->description;
          $req->detail = $detail;
          $req->payment = $co->payment_type;
          // dd($req);
          $so = new SalesOrderController;
          $so->store($req);

          DB::commit();
      } catch (Exception $e){
          DB::rollBack();
          throw new Exception(env('APP_DEBUG', false) ? $e->getMessage() : 'Something went wrong');
      }
    }
}
