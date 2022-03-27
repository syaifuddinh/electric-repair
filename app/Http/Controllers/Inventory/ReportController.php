<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Company;
use App\Model\Vehicle;
use Carbon\Carbon;
use Excel;
use PDF;
use bPDF;
use DB;
use Response;
use PHPExcel_Style_Fill;

class ReportController extends Controller
{
    public function index()
    {
        $data['company'] = companyAdmin(auth()->id());
        $data['vehicle'] = Vehicle::all();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function export(Request $request)
    {
        $res = null;

        switch($request->report_id) {
            case 1: $res = $this->report_1($request); break;
            default: $res = null;
        }
        
        return $res;
    }

    public function preview(Request $request)
    {
        $res = null;

        switch($request->report_id) {
            case 1: $res = $this->preview_1($request); break;
            default: $res = null;
        }

        return $res;
    }

    public function report_1(Request $request)
    {
        $data = $this->data_1($request);
        Excel::create('Laporan Penggunaan Sparepart - '.Carbon::now(), 
            function($excel) use ($data) {
                $excel->sheet('Data', function($sheet) use ($data) {
                    // STYLING ----------------------
                    $sheet->setStyle([
                        'font' => [
                            'name' => 'Calibri',
                            'size' => 11,
                        ],
                    ]);
                    
                    $sheet->cells('A1:K1', function($cells){
                        $cells->setFontWeight('bold');
                    });

                    $sheet->setColumnFormat([
                        'J' => '#,##0.00',
                        'K' => '#,##0.00'
                    ]);

                    // END STYLING -------------------
                    $sheet->SetCellValue('A1','No');
                    $sheet->SetCellValue('B1','Cabang');
                    $sheet->SetCellValue('C1','Kode Perawatan');
                    $sheet->SetCellValue('D1','Tanggal Pengajuan');
                    $sheet->SetCellValue('E1','Tanggal Realisasi');
                    $sheet->SetCellValue('F1','Nopol Kendaraan');
                    $sheet->SetCellValue('G1','Perawatan');
                    $sheet->SetCellValue('H1','Item');
                    $sheet->SetCellValue('I1','Qty');
                    $sheet->SetCellValue('J1','Biaya');
                    $sheet->SetCellValue('K1','Total');

                    foreach ($data as $key => $value) {
                        $idx = $key + 1;
                        $i=$key+2;
                        $sheet->SetCellValue('A'.$i, $idx);
                        $sheet->SetCellValue('B'.$i, $value->cabang);
                        $sheet->SetCellValue('C'.$i, $value->code);
                        $sheet->SetCellValue('D'.$i, $value->date_pengajuan);
                        $sheet->SetCellValue('E'.$i, $value->date_realisasi);
                        $sheet->SetCellValue('F'.$i, $value->nopol);
                        $sheet->SetCellValue('G'.$i, $value->perawatan);
                        $sheet->SetCellValue('H'.$i, $value->item_name);
                        $sheet->SetCellValue('I'.$i, $value->qty_realisasi);
                        $sheet->SetCellValue('J'.$i, $value->cost_realisasi);
                        $sheet->SetCellValue('K'.$i, $value->total_realisasi);
                    }
                });
            }
        )->export('xls');
    }

    public function preview_1(Request $request)
    {
        $filters = [];
        if($request->company_id) {
            $company = Company::find($request->company_id);
            $filters['company_name'] = $company->name;
        }

        if($request->vehicle_id) {
            $vehicle = Vehicle::find($request->vehicle_id);
            $filters['vehicle_nopol'] = $vehicle->nopol;
        }

        if($request->start_date && $request->end_date) {
            $filters['periode_pemakaian'] = [
                'start' => $request->start_date,
                'end' => $request->end_date
            ];
        }

        return view('report/sparepart_use', [
            'data' => $this->data_1($request),
            'filters' => $filters
        ]);
    }

    private function data_1(Request $request)
    {
        $data = DB::table("vehicle_maintenance_details as vmd")
            ->leftJoin("vehicle_maintenances as vm", "vm.id", "vmd.header_id")
            ->leftJoin("items as i", "i.id", "vmd.item_id")
            ->leftJoin("vehicles as v", "v.id", "vm.vehicle_id")
            ->leftJoin("companies as c", "c.id", "v.company_id")
            ->leftJoin("vehicle_maintenance_types as vmt", "vmt.id", "vmd.vehicle_maintenance_type_id")
            ->selectRaw("c.name as cabang, vm.code, vm.date_pengajuan, "
                . "vm.date_realisasi, v.nopol, vmt.name as perawatan, "
                . "i.name as item_name, vmd.qty_realisasi, vmd.cost_realisasi, vmd.total_realisasi")
            ->where("vm.status", 5)
            ->where("vm.is_internal", 1);
        
        if($request->start_request_date)
            $data->where("vm.date_pengajuan", ">=", Carbon::parse($request->start_request_date)->format('Y-m-d'));
        
        if($request->end_request_date)
            $data->where("vm.date_pengajuan", "<=", Carbon::parse($request->end_request_date)->format('Y-m-d'));
        
        if($request->start_date)
            $data->where("vm.date_realisasi", ">=", Carbon::parse($request->start_date)->format('Y-m-d'));
        
        if($request->end_date)
            $data->where("vm.date_realisasi", "<=", Carbon::parse($request->end_date)->format('Y-m-d'));
        
        if($request->company_id)
            $data->where("vm.company_id", $request->company_id);
        
        if($request->vehicle_id)
            $data->where("vm.vehicle_id", $request->vehicle_id);
        
        return $data->get();
    }
}