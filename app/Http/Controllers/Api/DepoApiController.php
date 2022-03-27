<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Abstracts\Depo\ContainerInspection;
use App\Abstracts\Depo\GateInContainer;
use App\Abstracts\Depo\MovementContainer;
use Carbon\Carbon;
use DataTables;

class DepoApiController extends Controller
{
    /*
      Date : 30-03-2021
      Description : Menampilkan daftar data inspeksi container dalam format jquery datatable
      Developer : Didin
      Status : Create
    */
    public function container_inspection_datatable(Request $request)
    {
        $item = ContainerInspection::query();
        $item = $item->select('container_inspections.id', 'container_inspections.date', 'container_inspections.description', 'contacts.name AS checker_name');

        if($request->filled('start_date')) {
            $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
            $item = $item->where('container_inspections.date', '>=', $start_date);
        } 

        if($request->filled('end_date')) {
            $end_date = Carbon::parse($request->end_date)->format('Y-m-d');
            $item = $item->where('container_inspections.date', '<=', $end_date);
        } 

        return DataTables::of($item)
        ->make(true);
    }

    /*
      Date : 30-03-2021
      Description : Menampilkan daftar data gate in container dalam format jquery datatable
      Developer : Didin
      Status : Create
    */
    public function gate_in_container_datatable(Request $request)
    {
        $item = GateInContainer::query($request->all());
        $item = $item->select('gate_in_containers.id', 'gate_in_containers.date', 'gate_in_containers.code', 'contacts.name AS owner_name', 'no_container', 'companies.name AS company_name', 'gate_in_container_statuses.name AS status_name');


        return DataTables::of($item)
        ->make(true);
    }

    /*
      Date : 30-03-2021
      Description : Menampilkan daftar data movement container dalam format jquery datatable
      Developer : Didin
      Status : Create
    */
    public function movement_container_datatable(Request $request)
    {
        $item = MovementContainer::query($request->all());
        $item = $item->select('movement_containers.id', 'movement_containers.date', 'movement_containers.code', 'contacts.name AS operator_name', 'companies.name AS company_name', 'movement_container_statuses.name AS status_name');


        return DataTables::of($item)
        ->make(true);
    }
}
