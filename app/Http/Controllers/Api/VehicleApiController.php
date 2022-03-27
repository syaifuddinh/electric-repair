<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Vehicle;
use App\Model\VehicleDistance;
use App\Model\VehicleChecklistItem;
use App\Model\VehicleMaintenance;
use App\Model\VehicleMaintenanceDetail;
use App\Model\TargetRate;
use DataTables;
use DB;
use Response;
use Illuminate\Database\Eloquent\Builder;

class VehicleApiController extends Controller
{
    public function vehicle_datatable(Request $request)
    {
        $wr = "1=1";
        if($request->company_id)
            $wr .= " AND vehicles.company_id = {$request->company_id}";

        $item = Vehicle::with('company','company.area','vehicle_variant','vehicle_variant.vehicle_type','supplier')
        ->whereRaw($wr)
        ->select('vehicles.*');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('vehicle.vehicle.detail')\" ui-sref=\"vehicle.vehicle.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            $html.="<a ng-show=\"roleList.includes('vehicle.vehicle.edit')\" ui-sref=\"vehicle.vehicle.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
            $html.="<a ng-show=\"roleList.includes('vehicle.vehicle.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->filter(function($query) use ($request ) {
            if(isset($request->is_internal)) {
                $query->whereIsInternal($request->is_internal ?? 0);
            }

            if(isset($request->vehicle_type_id)) {
                $vehicle_type_id = $request->vehicle_type_id;
                $query->whereHas('vehicle_variant', function (Builder $q) use ($vehicle_type_id) {
                    $q->where('vehicle_type_id', $vehicle_type_id);
                });
            }
        })
        ->editColumn('last_km', function($item){
            return number_format($item->last_km).' km';
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function vehicle_distance_datatable(Request $request)
    {
        $wr = '1=1';

        if($request->vehicle_id) {
            $wr .= " AND vehicle_distances.vehicle_id = {$request->vehicle_id}";
        } else {
            if($request->company_id)
                $wr .= " AND vehicles.company_id = {$request->company_id}";
        }

        if($request->start_date)
            $wr .= ' AND vehicle_distances.date_distance >= "'. dateDB($request->start_date) .'"';

        if($request->end_date)
            $wr .= ' AND vehicle_distances.date_distance <= "'. dateDB($request->end_date) .'"';

        $item = VehicleDistance::with('vehicle')
        ->leftJoin('vehicles', 'vehicles.id', 'vehicle_distances.vehicle_id')
        ->whereRaw($wr)
        ->select('vehicle_distances.*');

        return DataTables::of($item)
        ->editColumn('distance', function($item){
            return number_format($item->distance);
        })
        ->make(true);
    }
    public function vehicle_check_datatable(Request $request)
    {
        $wr = '1=1';

        if($request->company_id)
            $wr .= " AND vehicle_checklist_items.company_id = {$request->company_id}";

        if($request->vehicle_id)
            $wr .= " AND vehicle_checklist_items.vehicle_id = {$request->vehicle_id}";

        if($request->start_date)
            $wr .= ' AND vehicle_checklist_items.date_transaction >= "'. dateDB($request->start_date) .'"';

        if($request->end_date)
            $wr .= ' AND vehicle_checklist_items.date_transaction <= "'. dateDB($request->end_date) .'"';

        $item = VehicleChecklistItem::with('vehicle','company')
        ->whereRaw($wr)
        ->select('vehicle_checklist_items.*');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html = "<a ng-show=\"roleList.includes('vehicle.checklist.create')\" ui-sref=\"vehicle.vehicle_check.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
            $html .= "<a ng-show=\"roleList.includes('vehicle.checklist.create')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash' data-toggle='tooltip' title='Delete Data'></span></a>";
            return $html;
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function target_rate_datatable(Request $request)
    {
        $item = DB::select("select id,DATE_FORMAT(period,'%M') as months, plan, realisasi, DATE_FORMAT(updated_at,'%d-%m-%Y %H:%i') as up_at from target_rates where year(period) = '$request->year' order by period asc");

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('vehicle.vehicle.detail.detail.ritase.edit')\" ng-click=\"edits($item->id,$item->plan)\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>";
            return $html;
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function get_vehicle(Request $request)
    {
        $wr="1=1";
        if (isset($request->company_id)) {
            $wr.=" AND company_id = $request->company_id";
        }
        $c=Vehicle::whereRaw($wr)->select('id','code','nopol')->get();
        return Response::json($c, 200, [], JSON_NUMERIC_CHECK);
    }

    public function maintenance_datatable(Request $request)
    {
        $wr="1=1";
        if (isset($request->vehicle_id)) {
            $wr.=" AND vehicle_maintenances.vehicle_id = ".$request->vehicle_id;
        }
        if (isset($request->status)) {
            $wr.=" AND vehicle_maintenances.status = ".$request->status;
        }
// $item = VehicleMaintenance::with('vendor')->whereRaw($wr)->select('vehicle_maintenances.*');
        $item=DB::table('vehicle_maintenances')
        ->leftJoin('contacts','contacts.id','vehicle_maintenances.vendor_id')
        ->leftJoin('maintenance_statuses','maintenance_statuses.id','vehicle_maintenances.status')
        ->leftJoin('vehicle_maintenance_details','vehicle_maintenance_details.header_id','vehicle_maintenances.id')
        ->whereRaw($wr)
        ->selectRaw('
            vehicle_maintenances.*,
            contacts.name as vendor,
            maintenance_statuses.name as status_name,
            sum(vehicle_maintenance_details.total_rencana)+sum(distinct vehicle_maintenances.cost_rencana) as total_rencana,
            sum(vehicle_maintenance_details.total_realisasi)+sum(distinct vehicle_maintenances.cost_realisasi) as total_realisasi
            ')->groupBy('vehicle_maintenances.id');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('vehicle.vehicle.detail.maintenance.submission.detail')\" ui-sref=\"vehicle.vehicle.show.maintenance.show({id:$item->vehicle_id,vm_id:$item->id})\"><span class='fa fa-truck'></span></a>&nbsp;&nbsp;";
            if ($item->status<4) {
                $html.="<a ng-show=\"roleList.includes('vehicle.vehicle.detail.maintenance.submission.delete')\" ng-click='deletes($item->id)'><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
            }
            return $html;
        })
        ->editColumn('name', function($item){
            return "<a ui-sref='vehicle.vehicle.show.maintenance.show({id:$item->vehicle_id,vm_id:$item->id})'>$item->name</a>";
        })
        ->editColumn('km_rencana', function($item){
            return number_format($item->km_rencana);
        })
        ->editColumn('total_rencana', function($item){
            return number_format($item->total_rencana);
        })
        ->rawColumns(['action','status_name_html','name'])
        ->make(true);
    }
    public function maintenance_detail_datatable(Request $request, $vm_id)
    {
        $wr="1=1";
        $item = VehicleMaintenanceDetail::with('item','vehicle_maintenance_type')->where('header_id', $vm_id)->whereRaw($wr)->select('vehicle_maintenance_details.*');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            if ($item->qty_realisasi<1) {
                $html="<a ng-click='editItem($item->id,$item->qty_rencana,$item->cost_rencana)'><span class='fa fa-pencil-square-o'></span></a>&nbsp;&nbsp;";
            } else {
                $html="<a ng-click='editItem($item->id,$item->qty_realisasi,$item->cost_realisasi)'><span class='fa fa-pencil-square-o'></span></a>&nbsp;&nbsp;";
            }
            if ($item->header->status<4) {
                $html.="<a ng-click='deletes($item->id)'><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
            }
            return $html;
        })
        ->editColumn('qty_rencana', function($item){
            return number_format($item->qty_rencana);
        })
        ->editColumn('cost_rencana', function($item){
            return number_format($item->cost_rencana);
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function hitung_perawatan($vehicle_id)
    {
        $data['pengajuan']=VehicleMaintenance::where('vehicle_id', $vehicle_id)->where('status', 2)->count();
        $data['rencana']=VehicleMaintenance::where('vehicle_id', $vehicle_id)->where('status', 3)->count();
        $data['perawatan']=VehicleMaintenance::where('vehicle_id', $vehicle_id)->where('status', 4)->count();
        $data['selesai']=VehicleMaintenance::where('vehicle_id', $vehicle_id)->where('status', 5)->count();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
}
