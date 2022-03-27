<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Contact;
use DataTables;
use Response;
use DB;

class DriverApiController extends Controller
{
  public function driver_datatable(Request $request)
  {
    $wr="contacts.is_internal = 1 AND contacts.driver_status != 4 AND contacts.parent_id IS NULL";
    $item = Contact::with('company')->leftJoin('cities','cities.id','=','contacts.city_id')
            ->where('is_driver', 1)->whereRaw($wr);

    $company_id = $request->company_id;
    $company_id = $company_id != null ? $company_id : '';
    $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;

    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('is_active', $status) : $item;

    $item = $item->select('contacts.*',DB::raw("CONCAT(cities.type,' ',cities.name) as cityname"),DB::raw("IFNULL(contacts.code,'-') as codes"));

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('driver.driver.detail')\" ui-sref=\"driver.driver.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('driver.driver.edit')\" ui-sref=\"driver.driver.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('driver.driver.delete')\" ng-click='deletes($item->id)'><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->addColumn('action_fr_contact', function($item){
        $html="<a ng-show=\"roleList.includes('driver.driver.detail')\" ui-sref=\"contact.driver.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('driver.driver.edit')\" ui-sref=\"contact.driver.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('driver.driver.delete')\" ng-click='deletes($item->id)'><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->editColumn('is_active', function($item){
        $stt=[
          1 => '<span class="badge badge-success">AKTIF</span>',
          0 => '<span class="badge badge-danger">TIDAK AKTIF</span>',
        ];
        return $stt[$item->is_active];
      })
      ->editColumn('is_internal', function($item){
        $stt=[
          1 => 'Internal',

          0 => 'Eksternal',
        ];
        return $stt[$item->is_internal];
      })
      ->filterColumn('codes', function($query, $keyword) {
          $sql = "IFNULL(contacts.code,'-') like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->filterColumn('cityname', function($query, $keyword) {
          $sql = "CONCAT(cities.type,' ',cities.name) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->rawColumns(['action','is_active','action_fr_contact'])
      ->make(true);
  }
}
