<?php

namespace App\Http\Controllers\Export;

use App\Abstracts\Sales\SalesOrder;
use App\Abstracts\Sales\SalesOrderDetail;
use App\Abstracts\Setting\Company;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Invoice;
use Barryvdh\Snappy\Facades\SnappyPdf;
use App\Model\Quotation;
use App\Model\QuotationDetail;
use Barryvdh\DomPDF\Facade as PDF;
use bPDF;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Response;

class PdfController extends Controller
{
  /*
      Date : 13-07-2021
      Description : mengubah Package PDF dari SnappyPDF ke domPDF
      Developer : Hendra
      Status : Edit
    */
  public function print_quotation($id)
  {
    $quotation =  DB::table('quotations')
                ->whereId($id)
                ->first();
    if($quotation->path == null) {
        DB::table('quotations')
          ->whereId($id)
          ->update([
              'path' => Str::random(10)
          ]);
    }
    $data['remarks'] = DB::table('print_remarks')->first();
    $data['item']=Quotation::find($id);
    $data['detail']=QuotationDetail::where('header_id', $id)->get();
    // dd($data);
    return PDF::loadView('pdf.print_quotation',$data)->setPaper('folio','portrait')->stream();//->save('quotation.pdf'); 
    // return $outp->save('quotation.pdf');
  }  

  /*
      Date : 17-07-2021
      Description : Mencetak Sales Order dalam bentuk PDF
      Developer : Hendra
      Status : Create
    */
  public function print_sales_order(Request $request, $id)
  {
    $so = SalesOrder::show($id);
    $sod = DB::table('sales_orders')
              ->join('job_order_details as jod', 'jod.header_id', 'sales_orders.job_order_id')
              ->join('pieces', 'pieces.id', 'jod.piece_id')
              ->where('sales_orders.id', $id)
              ->select('sales_orders.code', 'jod.price', 'jod.total_price', 'jod.qty', 'jod.item_name', 'jod.description', 'pieces.name as unit_name')
              ->get();
    
    $cust = collect();
    if($so && $so->customer_id){
      $cust = DB::table('contacts')->select('contacts.name', 'address', 'email', 'postal_code', 'phone', 
                                            'cities.name as city', 'type as city_type', 'provinces.name as province', 'countries.name as country')
              ->leftJoin('cities', 'contacts.city_id', 'cities.id')
              ->leftJoin('provinces', 'provinces.id', 'cities.province_id')
              ->leftJoin('countries', 'countries.id', 'provinces.country_id')
              ->where('contacts.id', $so->customer_id)->first();
    }

    $inv = null;
    if($so && $so->invoice_id){
      $inv = Invoice::find($so->invoice_id);
    }

    $comp = null;
    if($so && $so->company_id){
      $comp = Company::show($so->company_id);
    }

    $ppn = 0;

    $data['remarks'] = DB::table('print_remarks')->first();
    $data['so'] = $so;
    $data['so_detail'] = $sod;
    $data['company'] = $comp;
    $data['customer'] = $cust;
    $data['invoice'] = $inv;
    $data['ppn'] = $ppn;
    // dd($data);
    // return view('pdf.print_sales_order', $data);

    return PDF::loadView('pdf.print_sales_order',$data)->setPaper('folio','portrait')->stream();
  }

  /*
      Date : 11-03-2020
      Description : Menampilkan cetakan penawaran dalam format PDF 
                    berdasarkan qr code
      Developer : Didin
      Status : Create
    */
  public function print_quotation_by_slug($slug)
  {
    $quotation = DB::table('quotations')
    ->wherePath($slug)
    ->first();
    if($quotation == null) {
        return response()->json(['message' => 'Halaman tidak ditemukan'], 404);
    }
    $data['item']=Quotation::find($quotation->id);
    $data['detail']=QuotationDetail::where('header_id', $quotation->id)->get();

    return SnappyPdf::loadView('pdf.print_quotation',$data)->setPaper('folio','portrait')->stream();//->save('quotation.pdf'); 
    // return $outp->save('quotation.pdf');
  }  

  public function unpaid_cost()
  {
    $mc = DB::table('manifest_costs')
    ->join('manifests', 'manifests.id', 'manifest_costs.header_id')
    ->join('cost_types', 'cost_types.id', 'manifest_costs.cost_type_id')
    ->whereRaw('manifest_costs.id NOT IN (SELECT manifest_cost_id FROM cash_transaction_details WHERE manifest_cost_id IS NOT NULL)')
    ->where('cost_types.type', 2)
    ->select('manifests.code', 'cost_types.name', 'manifest_costs.description', 'manifest_costs.total_price');

    $joc = DB::table('job_order_costs')
    ->join('job_orders', 'job_orders.id', 'job_order_costs.header_id')
    ->join('cost_types', 'cost_types.id', 'job_order_costs.cost_type_id')
    ->whereRaw('job_order_costs.id NOT IN (SELECT job_order_cost_id FROM cash_transaction_details WHERE job_order_cost_id IS NOT NULL)')
    ->where('cost_types.type', 2)
    ->select('job_orders.code', 'cost_types.name', 'job_order_costs.description', 'job_order_costs.total_price')
    ->union($mc)
    ->get();

    $data['costs']= $joc;

    return SnappyPdf::loadView('pdf.unpaid_cost',$data)->stream();//->save('quotation.pdf'); 
    // return $outp->save('quotation.pdf');
  }

  /*
      Date : 17-03-2020
      Description : Menampilkan laporan biaya selisih dalam format PDF
      Developer : Didin
      Status : Create
    */
  
  public function cost_balance()
  {
    $mc = DB::table('manifest_costs')
    ->join('manifests', 'manifests.id', 'manifest_costs.header_id')
    ->join('cost_types', 'cost_types.id', 'manifest_costs.cost_type_id')
    ->whereRaw('manifest_costs.id IN (SELECT manifest_cost_id FROM cash_transaction_details WHERE manifest_cost_id IS NOT NULL)')
    ->where('cost_types.type', 2)
    ->select('manifests.code', 'cost_types.name', 'manifest_costs.description', 'manifest_costs.total_price', DB::raw('IFNULL((SELECT SUM(amount) FROM cash_transaction_details WHERE manifest_cost_id = manifest_costs.id), 0) AS paid'));

    $joc = DB::table('job_order_costs')
    ->join('job_orders', 'job_orders.id', 'job_order_costs.header_id')
    ->join('cost_types', 'cost_types.id', 'job_order_costs.cost_type_id')
    ->whereRaw('job_order_costs.id IN (SELECT job_order_cost_id FROM cash_transaction_details WHERE job_order_cost_id IS NOT NULL)')
    ->where('cost_types.type', 2)
    ->select('job_orders.code', 'cost_types.name', 'job_order_costs.description', 'job_order_costs.total_price', DB::raw('IFNULL((SELECT SUM(amount) FROM cash_transaction_details WHERE job_order_cost_id = job_order_costs.id), 0) AS paid'))
    ->union($mc)
    ->get();

    $data['costs']= $joc;

    return SnappyPdf::loadView('pdf.cost_balance',$data)->stream();//->save('quotation.pdf'); 
    // return $outp->save('quotation.pdf');
  }
}
