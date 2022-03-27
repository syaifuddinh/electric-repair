<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Contact;
use App\Model\ContactAddress;
use DataTables;
use Response;
use Carbon\Carbon;
use DB;

class ContactApiController extends Controller
{
  public function contact_datatable(Request $request)
  {
    $wr="contacts.is_internal = 1";
    $item = Contact::query()
        ->leftJoin('cities','cities.id','=','contacts.city_id')
        ->whereRaw($wr)
        ->where('vendor_status_approve', 2);

    if($request->is_vendor == 1) {
        $item = $item->where("contacts.is_vendor", 1);
    }

    $is_active = $request->is_active == null ? '' : $request->is_active;
    $item = $is_active != '' ? $item->where('contacts.is_active', $is_active) : $item;

    $item = $item->select('contacts.id', 'contacts.name', 'contacts.address', 'contacts.phone', 'contacts.is_active', DB::raw("CONCAT(cities.type,' ',cities.name) as cityname"),DB::raw("IFNULL(contacts.code,'-') as codes"));

    $item = $item->when(!$request->order, function ($query) {
        $query->orderByDesc('contacts.created_at');
    })->where(function ($query) use ($request) {
        foreach ($request->filter_types as $key => $value){
            if ($value == 1) $query->orWhere($key, $value);
        }
    });

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('contact.contact.detail')\" ui-sref=\"contact.contact.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('contact.contact.edit')\" ui-sref=\"contact.contact.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        if($item->is_active == 1) {
            $html.="<a ng-show=\"roleList.includes('contact.contact.delete')\" ng-click='deletes($item->id)'><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        } else {
            $html.="<a ng-click='activate($item->id)'><span class='fa fa-check' data-toggle='tooltip' title='Aktifkan'></span></a>";          
        }
        return $html;
      })
      ->filterColumn('codes', function($query, $keyword) {
          $sql = "IFNULL(contacts.code,'-') like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->filterColumn('cityname', function($query, $keyword) {
          $sql = "CONCAT(cities.type,' ',cities.name) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function customer_amount()
  {
    $startPeriode = Carbon::now()->startOfMonth()->format('Y-m-d');
    $endPeriode = Carbon::now()->endOfMonth()->format('Y-m-d');

    $all = Contact::where('vendor_status_approve', 2)
    ->where("is_pelanggan", 1)->count();

    $new = Contact::where('vendor_status_approve', 2)
    ->where("is_pelanggan", 1)->whereBetween('created_at', [$startPeriode, $endPeriode])->count();

    $response['data'] = [
      'all' => $all,
      'new' => $new,
    ];

    return Response::json($response, 200);
  }
  public function customer_datatable(Request $request)
  {
    $item = Contact::leftJoin('cities','cities.id','=','contacts.city_id')->where('vendor_status_approve', 2)
            ->where("is_pelanggan", 1)
            ->select('contacts.*',
            DB::raw("CONCAT(cities.type,' ',cities.name) as cityname"),
            DB::raw("IFNULL(contacts.code,'-') as codes"),
            DB::raw("(SELECT MAX(shipment_date) FROM job_orders where customer_id = contacts.id) AS last_transaction_date"));

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('contact.customer.detail')\" ui-sref=\"contact.customer.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('contact.customer.edit')\" ui-sref=\"contact.customer.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('contact.customer.delete')\" ng-show=\"roleList.includes('')\" ng-click='deletes($item->id)'><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->filterColumn('codes', function($query, $keyword) {
          $sql = "IFNULL(contacts.code,'-') like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->filterColumn('cityname', function($query, $keyword) {
          $sql = "CONCAT(cities.type,' ',cities.name) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function contact_address_datatable($id)
  {
    $item = ContactAddress::with('contact','contact_address','contact_address.city')
    ->leftJoin('contacts as tagih','tagih.id','=','contact_addresses.contact_bill_id')
    ->leftJoin('address_types','address_types.id','=','contact_addresses.address_type_id')
    ->where('contact_id', $id)->select('contact_addresses.*','tagih.name as tagihname','address_types.name as typename');

    return DataTables::of($item)
      ->editColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('contact.contact.detail.address.detail')\" ui-sref=\"contact.contact.show.address.show({id:".$item->contact->id.",idaddress:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('contact.contact.detail.address.edit')\" ui-sref=\"contact.contact.show.address.edit({id:".$item->contact->id.",idaddress:$item->id,idcontact:$item->contact_address_id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('contact.contact.detail.address.delete')\" ng-click='deletes($item->id)'  data-toggle='tooltip' title='Hapus Data'><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
}
