<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Company;
use App\Model\Port;
use App\Model\Contact;
use App\Model\ContainerType;
use App\Model\Commodity;
use App\Model\Vessel;
use App\Model\Service;
use App\Utils\TransactionCode;
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
        $data['company']=companyAdmin(auth()->id());
        $data['service']=DB::table('service_groups')->get();
        $data['customer']=DB::table('contacts')->where('is_pelanggan', 1)->select('id','name')->get();
        $data['trayek']=DB::table('routes')->select('id','name')->get();
        $data['vehicle_type']=DB::table('vehicle_types')->select('id','name')->get();

        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function export(Request $request)
    {
        $rpt=$request->report_id;
        if ($rpt==1) {
            $res=$this->report_1($request);
        } else if ($rpt==2) {
            $res=$this->report_2($request);
        } else if ($rpt==3) {
            $res=$this->report_3($request);
        } else if ($rpt==4) {
            $res=$this->report_4($request);
        } else if ($rpt==5) {
            $res=$this->report_5($request);
        } else if ($rpt==6) {
            $res=$this->report_6($request);
        } else if ($rpt==9) {
            $res=$this->report_9($request);
        } else if ($rpt==10) {
            $res=$this->report_10($request);
        } else if ($rpt==11) {
            $res=$this->report_11($request);
        } else if ($rpt==12) {
            $res=$this->report_12($request);
        } else if ($rpt==13) {
            $res=$this->report_13($request);
        } else {
            $res=null;
        }
        return $res;
    }

    public function export_pdf(Request $request)
    {
        $rpt=$request->report_id;
        if ($rpt==1) {
            $res=$this->report_pdf_1($request);
        } else if ($rpt==2) {
            $res=$this->report_pdf_2($request);
        } else if ($rpt==3) {
            $res=$this->report_pdf_3($request);
        } else if ($rpt==4) {
            $res=$this->report_pdf_4($request);
        } else if ($rpt==5) {
            $res=$this->report_pdf_5($request);
        } else if ($rpt==6) {
            $res=$this->report_pdf_6($request);
        } else if ($rpt==9) {
            $res=$this->report_pdf_9($request);
        } else if ($rpt==10) {
            $res=$this->report_pdf_10($request);
        } else if ($rpt==11) {
            $res=$this->report_pdf_11($request);
        } else if ($rpt==12) {
            $res=$this->report_pdf_12($request);
        } else if ($rpt==13) {
            $res=$this->report_pdf_13($request);
        } else {
            $res=null;
        }
        return $res;
    }

    public function preview(Request $request)
    {
        $rpt=$request->report_id;
        if ($rpt==1) {
            $res=$this->preview_1($request);
        } else if ($rpt==2) {
            $res=$this->preview_2($request);
        } else if ($rpt==3) {
            $res=$this->preview_3($request);
        } else if ($rpt==4) {
            $res=$this->preview_4($request);
        } else if ($rpt==5) {
            $res=$this->preview_5($request);
        } else if ($rpt==6) {
            $res=$this->preview_6($request);
        } else if ($rpt==9) {
            $res=$this->preview_9($request);
        } else if ($rpt==10) {
            $res=$this->preview_10($request);
        } else if ($rpt==11) {
            $res=$this->preview_11($request);
        } else if ($rpt==12) {
            $res=$this->preview_12($request);
        } else if ($rpt==13) {
            $res=$this->preview_13($request);
        } else {
            $res=null;
        }
        return $res;
    }

    public function index_shipment_instruction()
    {
        $data['port']=Port::all();
        $data['container_type']=ContainerType::all();
        $data['commodity']=Commodity::all();
        $data['vessel']=Vessel::with('vendor')->get();
        $data['customer']=Contact::where('is_pelanggan', 1)->get();
        $data['sales']=Contact::where('is_sales', 1)->get();
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    public function shipment_instruction(Request $request)
    {
        $code = new TransactionCode(auth()->user()->company_id, 'shipmentInstruction');
        $code->setCode();
        $trx_code = $code->getCode();

        $data=[
            'attn' => $request->attn,
            'to' => Contact::find($request->to),
            'from' => Contact::find($request->from),
            'shipper' => Contact::find($request->shipper),
            'consignee' => Contact::find($request->consignee),
            'notify_party' => $request->notify_party,
            'pol' => Port::find($request->pol),
            'pod' => Port::find($request->pod),
            'commodity' => Commodity::find($request->commodity),
            'nw' => $request->nw,
            'gw' => $request->gw,
            'meassurement' => $request->meassurement,
            'freight' => $request->freight,
            'vessel' => Vessel::find($request->vessel),
            'etd' => $request->etd,
            'eta' => $request->eta,
            'qty' => $request->qty,
            'container_type' => ContainerType::find($request->container),
            'stuffing_date' => $request->stuffing_date,
            'auth' => auth()->user(),
            'code' => $trx_code
        ];
        return bPDF::loadView('pdf.shipment_instructions',$data)->setPaper('f4','potrait')->stream('shipment_instruction.pdf');

    }

    public function report_pdf_11($request)
    {

        $wr = "1=1 and jo.id is not null";

        if ($request->start_date && $request->end_date) {
            $startDate=Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate=Carbon::parse($request->end_date)->format('Y-m-d');
            $wr.=" and wo.date between '$startDate' and '$endDate'";
        }

        if ($request->customer_id) {
            $wr.=" and wo.customer_id = $request->customer_id";
        }

        if ($request->service_id) {
            $wr.=" and ifnull(sg.id,pl_sg.id) = $request->service_id";
        }

        if ($request->company_id) {
            $wr.=" and wo.company_id = $request->company_id";
        }

        $data = DB::table('work_orders as wo')
        ->leftJoin('work_order_details as wod', 'wo.id', 'wod.header_id')
        ->leftJoin('quotation_details as qd', 'wod.quotation_detail_id', 'qd.id')
        ->leftJoin('price_lists as pl', 'wod.price_list_id', 'pl.id')
        ->leftJoin('impositions as imp', 'imp.id', 'qd.imposition')
        ->leftJoin('pieces as p', 'p.id', 'qd.piece_id')
        ->leftJoin('pieces as pl_p', 'pl_p.id', 'pl.piece_id')
        ->leftJoin('services as s', 's.id', 'qd.service_id')
        ->leftJoin('services as pl_s', 'pl_s.id', 'pl.service_id')
        ->leftJoin('service_groups as sg', 'sg.id', 's.service_group_id')
        ->leftJoin('service_groups as pl_sg', 'pl_sg.id', 'pl_s.service_group_id')
        ->leftJoin('contacts as c', 'wo.customer_id', 'c.id')
        ->leftJoin('job_orders as jo','jo.work_order_detail_id','wod.id')
        ->leftJoin('job_order_details as jod','jod.header_id','jo.id')
        ->leftJoin('job_order_costs as joc', function($join){
            $join->on('joc.header_id','jo.id')->whereIn('joc.status',[3,5]);
        })
        ->whereRaw($wr)
        ->selectRaw("
            wo.*,
            if(jo.service_type_id in (2,3,4),sum(jo.total_unit),jod.qty) as qty,
            c.name AS customer_name,
            ifnull(sg.name,pl_sg.name) AS service_name,
            sum(joc.total_price)*count(distinct joc.id)/count(joc.id) as cost,
            ifnull(sg.id,pl_sg.id) as service_group_id,
            group_concat(joc.id) as joc_list,
            if(wod.quotation_detail_id is not null,1,2) as type_tarif,
            if(qd.service_type_id in (6,7),p.name,if(qd.service_type_id=2,'Kontainer',if(qd.service_type_id=3,'Unit',imp.name))) as imposition_name,
            if(pl.service_type_id in (6,7),pl_p.name,if(pl.service_type_id=2,'Kontainer',if(pl.service_type_id=3,'Unit','Kubikasi/Tonase/Item'))) as imposition_name_pl
            ")
        ->groupBy('wo.id','sg.id')
        ->orderBy('wo.date','desc')->get();

        $item['detail'] = $data;
        if(isset($request->company_id)) {
            $request->company_name = DB::table('companies')->where('id', $request->company_id)->first()->name;
        }
        if(isset($request->customer_id)) {
            $request->customer_name = DB::table('contacts')->where('id', $request->customer_id)->first()->name;
        }
        if(isset($request->service_id)) {
            $request->service_name = DB::table('service_groups')->where('id', $request->service_id)->first()->name;
        }
        $item['request'] = $request;
        $pdf = PDF::loadView('operational.laporan_operasional_ff', $item);
        return $pdf->download('Laporan Operasional FF.pdf');

    }

    public function report_pdf_12($request)
    {

        $item['units'] = $this->getInvoiceReport($request);
        $pdf = PDF::loadView('operational_report.invoice_report', $item);
        return $pdf->download('Laporan KPI Invoice.pdf');

    }

    public function report_pdf_13($request)
    {

        $item['units'] = $this->getInvoiceValidationReport($request);
        $pdf = PDF::loadView('operational_report.invoice_validation_report', $item);
        return $pdf->download('Laporan KPI Validasi Invoice.pdf');

    }

    public function report_1($request)
    {
        $wr="";
        if ($request->company_id) {
            $wr.=" and job_orders.company_id = $request->company_id";
        }
        if ($request->service_id) {
            $wr.=" and job_orders.service_id = $request->service_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        contacts.name as customer,
        sum(manifest_details.transported) as qty,
        pieces.name as satuan,
        services.name as service,
        sum(manifest_details.transported*job_order_details.price) as biaya,
        count(DISTINCT job_orders.work_order_id) as qty_wo
        FROM
        manifest_details
        left join job_order_details on job_order_details.id = manifest_details.job_order_detail_id
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join pieces on pieces.id = job_order_details.piece_id
        left join services on services.id = job_orders.service_id
        WHERE job_orders.service_type_id != 4 $wr
        GROUP BY
        job_orders.service_id,
        job_order_details.piece_id,
        job_orders.customer_id
        ORDER BY
        job_orders.customer_id asc
        ";
        $data=DB::select($sql);
        Excel::create('Rekap Pengiriman - '.Carbon::now(), function($excel) use ($data) {
            $excel->sheet('Data', function($sheet) use ($data){
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:F1', function($cells){
                    $cells->setFontWeight('bold');
                });
                $sheet->setColumnFormat([
                    'E' => '#,##0.00',
                    'F' => '#,##0.00'
                ]);


                $i=1;
                $sheet->SetCellValue('A'.$i,'Customer');
                $sheet->SetCellValue('B'.$i,'Qty WO');
                $sheet->SetCellValue('C'.$i,'Layanan');
                $sheet->SetCellValue('D'.$i,'Satuan');
                $sheet->SetCellValue('E'.$i,'Qty');
                $sheet->SetCellValue('F'.$i,'Total Biaya');

                foreach ($data as $key => $value) {
                    $i=$key+2;
                    $sheet->SetCellValue('A'.$i,$value->customer);
                    $sheet->SetCellValue('B'.$i,$value->qty_wo);
                    $sheet->SetCellValue('C'.$i,$value->service);
                    $sheet->SetCellValue('D'.$i,$value->satuan);
                    $sheet->SetCellValue('E'.$i,$value->qty);
                    $sheet->SetCellValue('F'.$i,$value->biaya);
                }

// $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');

    }
    public function preview_1($request)
    {
        $wr="";
        if ($request->company_id) {
            $wr.=" and job_orders.company_id = $request->company_id";
        }
        if ($request->service_id) {
            $wr.=" and job_orders.service_id = $request->service_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        contacts.name as customer,
        sum(manifest_details.transported) as qty,
        pieces.name as satuan,
        services.name as service,
        sum(manifest_details.transported*job_order_details.price) as biaya,
        count(DISTINCT job_orders.work_order_id) as qty_wo
        FROM
        manifest_details
        left join job_order_details on job_order_details.id = manifest_details.job_order_detail_id
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join pieces on pieces.id = job_order_details.piece_id
        left join services on services.id = job_orders.service_id
        WHERE job_orders.service_type_id != 4 $wr
        GROUP BY
        job_orders.service_id,
        job_order_details.piece_id,
        job_orders.customer_id
        ORDER BY
        job_orders.customer_id asc
        ";
        $data=DB::select($sql);
        $resp['units'] = $data;

        return view('operational_report/rekap_pengiriman', $resp);


    }
    public function report_2($request)
    {
        $wr="";
        if ($request->company_id) {
            $wr.=" and work_orders.company_id = $request->company_id";
        }
        if ($request->service_id) {
            $wr.=" and job_orders.service_id = $request->service_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        if ($request->route_id) {
            $wr.=" and job_orders.route_id = $request->route_id";
        }
        if ($request->vehicle_type_id) {
            $wr.=" and job_orders.vehicle_type_id = $request->vehicle_type_id";
        }
        if ($request->code_wo) {
            $wr.=" and work_orders.code like '%$request->code_wo%'";
        }
        $sql="
        SELECT
        contacts.name as customer,
        manifest_details.transported as qty,
        work_orders.code as code_wo,
        date(work_orders.created_at) as date_wo,
        pieces.name as satuan,
        services.name as service,
        routes.name as trayek,
        IF(manifests.container_id is null,vehicle_types.name,CONCAT(container_types.code,' - ',container_types.name)) as vehicle_type,
        IF(manifests.container_id is null,manifests.depart,containers.stuffing) as muat,
        IF(manifests.container_id is null,manifests.arrive,containers.stripping) as bongkar,
        (manifest_details.transported*job_order_details.price) as biaya,
        job_order_details.description as description
        FROM
        manifest_details
        left join job_order_details on job_order_details.id = manifest_details.job_order_detail_id
        left join job_orders on job_orders.id = job_order_details.header_id
        left join work_orders on work_orders.id = job_orders.work_order_id
        left join contacts on contacts.id = job_orders.customer_id
        left join pieces on pieces.id = job_order_details.piece_id
        left join services on services.id = job_orders.service_id
        left join routes on routes.id = job_orders.route_id
        left join vehicle_types on vehicle_types.id = job_orders.vehicle_type_id
        left join manifests on manifests.id = manifest_details.header_id
        left join containers on containers.id = manifests.container_id
        left join container_types on container_types.id = containers.container_type_id
        WHERE job_orders.service_type_id != 4
        $wr
        ORDER BY manifest_details.created_at DESC
        ";
        $data=DB::select($sql);
        Excel::create('Pengiriman - '.Carbon::now(), function($excel) use ($data) {
            $excel->sheet('Data', function($sheet) use ($data){
// STYLING ----------------------
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:L1', function($cells){
                    $cells->setFontWeight('bold');
                });
                $sheet->setColumnFormat([
                    'H' => '#,##0.00',
                    'K' => '#,##0.00'
                ]);
// END STYLING -------------------
                $sheet->SetCellValue('A1','Customer');
                $sheet->SetCellValue('B1','WO');
                $sheet->SetCellValue('C1','Tanggal WO');
                $sheet->SetCellValue('D1','Layanan');
                $sheet->SetCellValue('E1','Trayek');
                $sheet->SetCellValue('F1','Satuan');
                $sheet->SetCellValue('G1','Tipe Kendaraan');
                $sheet->SetCellValue('H1','Qty');
                $sheet->SetCellValue('I1','Tgl Muat');
                $sheet->SetCellValue('J1','Tgl Bongkar');
                $sheet->SetCellValue('K1','Total Biaya');
                $sheet->SetCellValue('L1','Keterangan');

                foreach ($data as $key => $value) {
                    $i=$key+2;
                    $sheet->SetCellValue('A'.$i,$value->customer);
                    $sheet->SetCellValue('B'.$i,$value->code_wo);
                    $sheet->SetCellValue('C'.$i,$value->date_wo);
                    $sheet->SetCellValue('D'.$i,$value->service);
                    $sheet->SetCellValue('E'.$i,$value->trayek);
                    $sheet->SetCellValue('F'.$i,$value->satuan);
                    $sheet->SetCellValue('G'.$i,$value->vehicle_type);
                    $sheet->SetCellValue('H'.$i,$value->qty);
                    $sheet->SetCellValue('I'.$i,$value->muat);
                    $sheet->SetCellValue('J'.$i,$value->bongkar);
                    $sheet->SetCellValue('K'.$i,$value->biaya);
                    $sheet->SetCellValue('L'.$i,$value->description);
                }

// $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');

    }
    public function preview_2($request)
    {
        $wr="";
        if ($request->company_id) {
            $wr.=" and work_orders.company_id = $request->company_id";
        }
        if ($request->service_id) {
            $wr.=" and job_orders.service_id = $request->service_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        if ($request->route_id) {
            $wr.=" and job_orders.route_id = $request->route_id";
        }
        if ($request->vehicle_type_id) {
            $wr.=" and job_orders.vehicle_type_id = $request->vehicle_type_id";
        }
        if ($request->code_wo) {
            $wr.=" and work_orders.code like '%$request->code_wo%'";
        }
        $sql="
        SELECT
        contacts.name as customer,
        manifest_details.transported as qty,
        work_orders.code as code_wo,
        date(work_orders.created_at) as date_wo,
        pieces.name as satuan,
        services.name as service,
        routes.name as trayek,
        IF(manifests.container_id is null,vehicle_types.name,CONCAT(container_types.code,' - ',container_types.name)) as vehicle_type,
        IF(manifests.container_id is null,manifests.depart,containers.stuffing) as muat,
        IF(manifests.container_id is null,manifests.arrive,containers.stripping) as bongkar,
        (manifest_details.transported*job_order_details.price) as biaya,
        job_order_details.description as description
        FROM
        manifest_details
        left join job_order_details on job_order_details.id = manifest_details.job_order_detail_id
        left join job_orders on job_orders.id = job_order_details.header_id
        left join work_orders on work_orders.id = job_orders.work_order_id
        left join contacts on contacts.id = job_orders.customer_id
        left join pieces on pieces.id = job_order_details.piece_id
        left join services on services.id = job_orders.service_id
        left join routes on routes.id = job_orders.route_id
        left join vehicle_types on vehicle_types.id = job_orders.vehicle_type_id
        left join manifests on manifests.id = manifest_details.header_id
        left join containers on containers.id = manifests.container_id
        left join container_types on container_types.id = containers.container_type_id
        WHERE job_orders.service_type_id != 4
        $wr
        ORDER BY manifest_details.created_at DESC
        ";
        $data=DB::select($sql);
        $resp['units'] = $data;

        return view('operational_report/pengiriman', $resp);

    }
    public function report_3($request)
    {
// dd($request);
        $wr="";
        if ($request->company_id) {
            $wr.=" and job_orders.company_id = $request->company_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        count(distinct job_orders.work_order_id) as qty_wo,
        sum(job_order_details.qty) as qty,
        sum(job_order_details.total_price) as total_price,
        services.name as service,
        contacts.name as customer,
        pieces.name as satuan
        FROM
        job_order_details
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join services on services.id = job_orders.service_id
        left join pieces on pieces.id = job_orders.piece_id
        where job_orders.service_type_id = 6 $wr
        group by
        job_orders.service_id,
        job_orders.piece_id,
        job_orders.customer_id
        order by
        job_orders.customer_id
        ";
        $data=DB::select($sql);
        Excel::create('Rekap Kepabeanan - '.Carbon::now(), function($excel) use ($data) {
            $excel->sheet('Data', function($sheet) use ($data){
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:F1', function($cells){
                    $cells->setFontWeight('bold');
// $cells->setAlignment('center');
                });
                $sheet->setColumnFormat([
                    'C' => '0',
                    'E' => '#,##0.00',
                    'F' => '#,##0.00'
                ]);


                $i=1;
                $sheet->SetCellValue('A'.$i,'Customer');
                $sheet->SetCellValue('B'.$i,'Layanan');
                $sheet->SetCellValue('C'.$i,'Jml WO');
                $sheet->SetCellValue('D'.$i,'Satuan');
                $sheet->SetCellValue('E'.$i,'Qty');
                $sheet->SetCellValue('F'.$i,'Total Biaya');

                foreach ($data as $key => $value) {
                    $i=$key+2;
                    $sheet->SetCellValue('A'.$i,$value->customer);
                    $sheet->SetCellValue('B'.$i,$value->service);
                    $sheet->SetCellValue('C'.$i,$value->qty_wo);
                    $sheet->SetCellValue('D'.$i,$value->satuan);
                    $sheet->SetCellValue('E'.$i,$value->qty);
                    $sheet->SetCellValue('F'.$i,$value->total_price);
                }

// $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');

    }
    public function preview_3($request)
    {
// dd($request);
        $wr="";
        if ($request->company_id) {
            $wr.=" and job_orders.company_id = $request->company_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        count(distinct job_orders.work_order_id) as qty_wo,
        sum(job_order_details.qty) as qty,
        sum(job_order_details.total_price) as total_price,
        services.name as service,
        contacts.name as customer,
        pieces.name as satuan
        FROM
        job_order_details
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join services on services.id = job_orders.service_id
        left join pieces on pieces.id = job_orders.piece_id
        where job_orders.service_type_id = 6 $wr
        group by
        job_orders.service_id,
        job_orders.piece_id,
        job_orders.customer_id
        order by
        job_orders.customer_id
        ";
        $data=DB::select($sql);
        $resp['units'] = $data;

        return view('operational_report/rekap_kepabeanan', $resp);

    }
    public function report_4($request)
    {
// dd($request);
        $wr="";
        if ($request->company_id) {
            $wr.=" and work_orders.company_id = $request->company_id";
        }
        if ($request->code_wo) {
            $wr.=" and work_orders.code like '%$request->code_wo%'";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        job_orders.no_bl,
        job_orders.aju_number,
        job_order_details.qty,
        job_order_details.total_price,
        services.name as service,
        contacts.name as customer,
        work_orders.code as code_wo,
        pieces.name as satuan,
        date(work_orders.created_at) as date_wo
        FROM
        job_order_details
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join services on services.id = job_orders.service_id
        left join pieces on pieces.id = job_orders.piece_id
        left join work_orders on work_orders.id = job_orders.work_order_id
        where job_orders.service_type_id = 6 $wr
        ";
        $data=DB::select($sql);
        Excel::create('Kepabeanan - '.Carbon::now(), function($excel) use ($data) {
            $excel->sheet('Data', function($sheet) use ($data){
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:I1', function($cells){
                    $cells->setFontWeight('bold');
// $cells->setAlignment('center');
                });
                $sheet->setColumnFormat([
                    'E' => '@',
                    'F' => '@',
                    'H' => '#,##0.00',
                    'I' => '#,##0.00'
                ]);


                $i=1;
                $sheet->SetCellValue('A'.$i,'Customer');
                $sheet->SetCellValue('B'.$i,'No. WO');
                $sheet->SetCellValue('C'.$i,'Tgl. Wo');
                $sheet->SetCellValue('D'.$i,'Layanan');
                $sheet->SetCellValue('E'.$i,'No. AJU');
                $sheet->SetCellValue('F'.$i,'No. BL');
                $sheet->SetCellValue('G'.$i,'Satuan');
                $sheet->SetCellValue('H'.$i,'Qty');
                $sheet->SetCellValue('I'.$i,'Total Biaya');

                foreach ($data as $key => $value) {
                    $i=$key+2;
                    $sheet->SetCellValue('A'.$i,$value->customer);
                    $sheet->SetCellValue('B'.$i,$value->code_wo);
                    $sheet->SetCellValue('C'.$i,$value->date_wo);
                    $sheet->SetCellValue('D'.$i,$value->service);
                    $sheet->SetCellValue('E'.$i,$value->aju_number);
                    $sheet->SetCellValue('F'.$i,$value->no_bl);
                    $sheet->SetCellValue('G'.$i,$value->satuan);
                    $sheet->SetCellValue('H'.$i,$value->qty);
                    $sheet->SetCellValue('I'.$i,$value->total_price);
                }

// $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');

    }
    public function preview_4($request)
    {
// dd($request);
        $wr="";
        if ($request->company_id) {
            $wr.=" and work_orders.company_id = $request->company_id";
        }
        if ($request->code_wo) {
            $wr.=" and work_orders.code like '%$request->code_wo%'";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        job_orders.no_bl,
        job_orders.aju_number,
        job_order_details.qty,
        job_order_details.total_price,
        services.name as service,
        contacts.name as customer,
        work_orders.code as code_wo,
        pieces.name as satuan,
        date(work_orders.created_at) as date_wo
        FROM
        job_order_details
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join services on services.id = job_orders.service_id
        left join pieces on pieces.id = job_orders.piece_id
        left join work_orders on work_orders.id = job_orders.work_order_id
        where job_orders.service_type_id = 6 $wr
        ";
        $data=DB::select($sql);
        $resp['units'] = $data;

        return view('operational_report/kepabeanan', $resp);

    }
    public function report_5($request)
    {
// dd($request);
        $wr="";
        if ($request->company_id) {
            $wr.=" and job_orders.company_id = $request->company_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        count(distinct job_orders.work_order_id) as qty_wo,
        sum(job_order_details.qty) as qty,
        sum(job_order_details.total_price) as total_price,
        services.name as service,
        contacts.name as customer,
        pieces.name as satuan
        FROM
        job_order_details
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join services on services.id = job_orders.service_id
        left join pieces on pieces.id = job_orders.piece_id
        where job_orders.service_type_id = 7 $wr
        group by
        job_orders.service_id,
        job_orders.piece_id,
        job_orders.customer_id
        order by
        job_orders.customer_id
        ";
        $data=DB::select($sql);
        Excel::create('Rekap Jasa Lainnya - '.Carbon::now(), function($excel) use ($data) {
            $excel->sheet('Data', function($sheet) use ($data){
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:F1', function($cells){
                    $cells->setFontWeight('bold');
// $cells->setAlignment('center');
                });
                $sheet->setColumnFormat([
                    'C' => '0',
                    'E' => '#,##0.00',
                    'F' => '#,##0.00'
                ]);


                $i=1;
                $sheet->SetCellValue('A'.$i,'Customer');
                $sheet->SetCellValue('B'.$i,'Layanan');
                $sheet->SetCellValue('C'.$i,'Jml WO');
                $sheet->SetCellValue('D'.$i,'Satuan');
                $sheet->SetCellValue('E'.$i,'Qty');
                $sheet->SetCellValue('F'.$i,'Total Biaya');

                foreach ($data as $key => $value) {
                    $i=$key+2;
                    $sheet->SetCellValue('A'.$i,$value->customer);
                    $sheet->SetCellValue('B'.$i,$value->service);
                    $sheet->SetCellValue('C'.$i,$value->qty_wo);
                    $sheet->SetCellValue('D'.$i,$value->satuan);
                    $sheet->SetCellValue('E'.$i,$value->qty);
                    $sheet->SetCellValue('F'.$i,$value->total_price);
                }

// $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');

    }
    public function preview_5($request)
    {
// dd($request);
        $wr="";
        if ($request->company_id) {
            $wr.=" and job_orders.company_id = $request->company_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        count(distinct job_orders.work_order_id) as qty_wo,
        sum(job_order_details.qty) as qty,
        sum(job_order_details.total_price) as total_price,
        services.name as service,
        contacts.name as customer,
        pieces.name as satuan
        FROM
        job_order_details
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join services on services.id = job_orders.service_id
        left join pieces on pieces.id = job_orders.piece_id
        where job_orders.service_type_id = 7 $wr
        group by
        job_orders.service_id,
        job_orders.piece_id,
        job_orders.customer_id
        order by
        job_orders.customer_id
        ";
        $data=DB::select($sql);
        $resp['units'] = $data;

        return view('operational_report/rekap_jasa_lainnya', $resp);

    }
    public function report_6($request)
    {
// dd($request);
        $wr="";
        if ($request->company_id) {
            $wr.=" and work_orders.company_id = $request->company_id";
        }
        if ($request->code_wo) {
            $wr.=" and work_orders.code like '%$request->code_wo%'";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        job_orders.description as komoditas,
        job_order_details.qty,
        job_order_details.total_price,
        services.name as service,
        contacts.name as customer,
        work_orders.code as code_wo,
        pieces.name as satuan,
        date(work_orders.created_at) as date_wo,
        '-' as description
        FROM
        job_order_details
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join services on services.id = job_orders.service_id
        left join pieces on pieces.id = job_orders.piece_id
        left join work_orders on work_orders.id = job_orders.work_order_id
        where job_orders.service_type_id = 7
        ";
        $data=DB::select($sql);
        Excel::create('Jasa Lainnya - '.Carbon::now(), function($excel) use ($data) {
            $excel->sheet('Data', function($sheet) use ($data){
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:I1', function($cells){
                    $cells->setFontWeight('bold');
// $cells->setAlignment('center');
                });
                $sheet->setColumnFormat([
                    'E' => '@',
                    'I' => '@',
                    'G' => '#,##0.00',
                    'H' => '#,##0.00'
                ]);


                $i=1;
                $sheet->SetCellValue('A'.$i,'Customer');
                $sheet->SetCellValue('B'.$i,'No. WO');
                $sheet->SetCellValue('C'.$i,'Tgl. Wo');
                $sheet->SetCellValue('D'.$i,'Layanan');
                $sheet->SetCellValue('E'.$i,'Komoditas');
                $sheet->SetCellValue('F'.$i,'Satuan');
                $sheet->SetCellValue('G'.$i,'Qty');
                $sheet->SetCellValue('H'.$i,'Total Biaya');
                $sheet->SetCellValue('I'.$i,'Keterangan');

                foreach ($data as $key => $value) {
                    $i=$key+2;
                    $sheet->SetCellValue('A'.$i,$value->customer);
                    $sheet->SetCellValue('B'.$i,$value->code_wo);
                    $sheet->SetCellValue('C'.$i,$value->date_wo);
                    $sheet->SetCellValue('D'.$i,$value->service);
                    $sheet->SetCellValue('E'.$i,$value->komoditas);
                    $sheet->SetCellValue('F'.$i,$value->satuan);
                    $sheet->SetCellValue('G'.$i,$value->qty);
                    $sheet->SetCellValue('H'.$i,$value->total_price);
                    $sheet->SetCellValue('I'.$i,$value->description);
                }

// $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');

    }
    public function preview_6($request)
    {
// dd($request);
        $wr="";
        if ($request->company_id) {
            $wr.=" and work_orders.company_id = $request->company_id";
        }
        if ($request->code_wo) {
            $wr.=" and work_orders.code like '%$request->code_wo%'";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        job_orders.description as komoditas,
        job_order_details.qty,
        job_order_details.total_price,
        services.name as service,
        contacts.name as customer,
        work_orders.code as code_wo,
        pieces.name as satuan,
        date(work_orders.created_at) as date_wo,
        '-' as description
        FROM
        job_order_details
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join services on services.id = job_orders.service_id
        left join pieces on pieces.id = job_orders.piece_id
        left join work_orders on work_orders.id = job_orders.work_order_id
        where job_orders.service_type_id = 7
        ";
        $data=DB::select($sql);
        $resp['units'] = $data;

        return view('operational_report/jasa_lainnya', $resp);

    }
    public function report_9($request)
    {
        $wr="";
        if ($request->company_id) {
            $wr.=" and job_orders.company_id = $request->company_id";
        }
        if ($request->service_id) {
            $wr.=" and job_orders.service_id = $request->service_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        contacts.name as customer,
        sum(manifest_details.transported) as qty,
        pieces.name as satuan,
        services.name as service,
        sum(manifest_details.transported*job_order_details.price) as biaya,
        count(DISTINCT job_orders.work_order_id) as qty_wo
        FROM
        manifest_details
        left join job_order_details on job_order_details.id = manifest_details.job_order_detail_id
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join pieces on pieces.id = job_order_details.piece_id
        left join services on services.id = job_orders.service_id
        WHERE job_orders.service_type_id = 4 $wr
        GROUP BY
        job_orders.service_id,
        job_order_details.piece_id,
        job_orders.customer_id
        ORDER BY
        job_orders.customer_id asc
        ";
        $data=DB::select($sql);
        Excel::create('Rekap Transportasi - '.Carbon::now(), function($excel) use ($data) {
            $excel->sheet('Data', function($sheet) use ($data){
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:F1', function($cells){
                    $cells->setFontWeight('bold');
                });
                $sheet->setColumnFormat([
                    'E' => '#,##0.00',
                    'F' => '#,##0.00'
                ]);


                $i=1;
                $sheet->SetCellValue('A'.$i,'Customer');
                $sheet->SetCellValue('B'.$i,'Qty WO');
                $sheet->SetCellValue('C'.$i,'Layanan');
                $sheet->SetCellValue('D'.$i,'Satuan');
                $sheet->SetCellValue('E'.$i,'Qty');
                $sheet->SetCellValue('F'.$i,'Total Biaya');

                foreach ($data as $key => $value) {
                    $i=$key+2;
                    $sheet->SetCellValue('A'.$i,$value->customer);
                    $sheet->SetCellValue('B'.$i,$value->qty_wo);
                    $sheet->SetCellValue('C'.$i,$value->service);
                    $sheet->SetCellValue('D'.$i,$value->satuan);
                    $sheet->SetCellValue('E'.$i,$value->qty);
                    $sheet->SetCellValue('F'.$i,$value->biaya);
                }

// $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');

    }
    public function preview_9($request)
    {
        $wr="";
        if ($request->company_id) {
            $wr.=" and job_orders.company_id = $request->company_id";
        }
        if ($request->service_id) {
            $wr.=" and job_orders.service_id = $request->service_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        $sql="
        SELECT
        contacts.name as customer,
        sum(manifest_details.transported) as qty,
        pieces.name as satuan,
        services.name as service,
        sum(manifest_details.transported*job_order_details.price) as biaya,
        count(DISTINCT job_orders.work_order_id) as qty_wo
        FROM
        manifest_details
        left join job_order_details on job_order_details.id = manifest_details.job_order_detail_id
        left join job_orders on job_orders.id = job_order_details.header_id
        left join contacts on contacts.id = job_orders.customer_id
        left join pieces on pieces.id = job_order_details.piece_id
        left join services on services.id = job_orders.service_id
        WHERE job_orders.service_type_id = 4 $wr
        GROUP BY
        job_orders.service_id,
        job_order_details.piece_id,
        job_orders.customer_id
        ORDER BY
        job_orders.customer_id asc
        ";
        $data=DB::select($sql);
        $resp['units'] = $data;

        return view('operational_report/rekap_transportasi', $resp);

    }
    public function report_10($request)
    {
        $wr="";
        if ($request->company_id) {
            $wr.=" and work_orders.company_id = $request->company_id";
        }
        if ($request->service_id) {
            $wr.=" and job_orders.service_id = $request->service_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        if ($request->route_id) {
            $wr.=" and job_orders.route_id = $request->route_id";
        }
        if ($request->vehicle_type_id) {
            $wr.=" and job_orders.vehicle_type_id = $request->vehicle_type_id";
        }
        if ($request->code_wo) {
            $wr.=" and work_orders.code like '%$request->code_wo%'";
        }
        $sql="
        SELECT
        contacts.name as customer,
        manifest_details.transported as qty,
        work_orders.code as code_wo,
        date(work_orders.created_at) as date_wo,
        pieces.name as satuan,
        services.name as service,
        IF(manifests.container_id is null,vehicle_types.name,CONCAT(container_types.code,' - ',container_types.name)) as vehicle_type,
        (manifest_details.transported*job_order_details.price) as biaya,
        job_order_details.description as description
        FROM
        manifest_details
        left join job_order_details on job_order_details.id = manifest_details.job_order_detail_id
        left join job_orders on job_orders.id = job_order_details.header_id
        left join work_orders on work_orders.id = job_orders.work_order_id
        left join contacts on contacts.id = job_orders.customer_id
        left join pieces on pieces.id = job_order_details.piece_id
        left join services on services.id = job_orders.service_id
        left join vehicle_types on vehicle_types.id = job_orders.vehicle_type_id
        left join manifests on manifests.id = manifest_details.header_id
        left join containers on containers.id = manifests.container_id
        left join container_types on container_types.id = containers.container_type_id
        WHERE job_orders.service_type_id = 4
        $wr
        ORDER BY manifest_details.created_at DESC
        ";
        $data=DB::select($sql);
        Excel::create('Transportasi - '.Carbon::now(), function($excel) use ($data) {
            $excel->sheet('Data', function($sheet) use ($data){
// STYLING ----------------------
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:I1', function($cells){
                    $cells->setFontWeight('bold');
                });
                $sheet->setColumnFormat([
                    'G' => '#,##0.00',
                    'H' => '#,##0.00'
                ]);
// END STYLING -------------------
                $sheet->SetCellValue('A1','Customer');
                $sheet->SetCellValue('B1','WO');
                $sheet->SetCellValue('C1','Tanggal WO');
                $sheet->SetCellValue('D1','Layanan');
                $sheet->SetCellValue('E1','Satuan');
                $sheet->SetCellValue('F1','Tipe Kendaraan');
                $sheet->SetCellValue('G1','Qty');
                $sheet->SetCellValue('H1','Total Biaya');
                $sheet->SetCellValue('I1','Keterangan');

                foreach ($data as $key => $value) {
                    $i=$key+2;
                    $sheet->SetCellValue('A'.$i,$value->customer);
                    $sheet->SetCellValue('B'.$i,$value->code_wo);
                    $sheet->SetCellValue('C'.$i,$value->date_wo);
                    $sheet->SetCellValue('D'.$i,$value->service);
                    $sheet->SetCellValue('E'.$i,$value->satuan);
                    $sheet->SetCellValue('F'.$i,$value->vehicle_type);
                    $sheet->SetCellValue('G'.$i,$value->qty);
                    $sheet->SetCellValue('H'.$i,$value->biaya);
                    $sheet->SetCellValue('I'.$i,$value->description);
                }

// $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');

    }
    public function preview_10($request)
    {
        $wr="";
        if ($request->company_id) {
            $wr.=" and work_orders.company_id = $request->company_id";
        }
        if ($request->service_id) {
            $wr.=" and job_orders.service_id = $request->service_id";
        }
        if ($request->customer_id) {
            $wr.=" and job_orders.customer_id = $request->customer_id";
        }
        if ($request->route_id) {
            $wr.=" and job_orders.route_id = $request->route_id";
        }
        if ($request->vehicle_type_id) {
            $wr.=" and job_orders.vehicle_type_id = $request->vehicle_type_id";
        }
        if ($request->code_wo) {
            $wr.=" and work_orders.code like '%$request->code_wo%'";
        }
        $sql="
        SELECT
        contacts.name as customer,
        manifest_details.transported as qty,
        work_orders.code as code_wo,
        date(work_orders.created_at) as date_wo,
        pieces.name as satuan,
        services.name as service,
        IF(manifests.container_id is null,vehicle_types.name,CONCAT(container_types.code,' - ',container_types.name)) as vehicle_type,
        (manifest_details.transported*job_order_details.price) as biaya,
        job_order_details.description as description
        FROM
        manifest_details
        left join job_order_details on job_order_details.id = manifest_details.job_order_detail_id
        left join job_orders on job_orders.id = job_order_details.header_id
        left join work_orders on work_orders.id = job_orders.work_order_id
        left join contacts on contacts.id = job_orders.customer_id
        left join pieces on pieces.id = job_order_details.piece_id
        left join services on services.id = job_orders.service_id
        left join vehicle_types on vehicle_types.id = job_orders.vehicle_type_id
        left join manifests on manifests.id = manifest_details.header_id
        left join containers on containers.id = manifests.container_id
        left join container_types on container_types.id = containers.container_type_id
        WHERE job_orders.service_type_id = 4
        $wr
        ORDER BY manifest_details.created_at DESC
        ";
        $data=DB::select($sql);
        $resp['units'] = $data;

        return view('operational_report/transportasi', $resp);

    }


    public function report_12($request)
    {
        $units = $this->getInvoiceReport($request);
        
        Excel::create('Laporan KPI Invoice - '.Carbon::now(), function($excel) use ($units) {
            $excel->sheet('Laporan KPI Invoice', function($sheet) use ($units){
                // STYLING ----------------------
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:J1', function($cells){
                    $cells->setFontWeight('bold');
                });

                $sheet->SetCellValue('A1','No');
                $sheet->SetCellValue('B1','No JO');
                $sheet->SetCellValue('C1','Tanggal Pembuatan JO');
                $sheet->SetCellValue('D1','No. Invoice');
                $sheet->SetCellValue('E1','Pembuat JO');
                $sheet->SetCellValue('F1','Tanggal Penyelesaian');
                $sheet->SetCellValue('G1','Tanggal Target Pembuatan Invoice');
                $sheet->SetCellValue('H1','Tanggal Pembuatan Invoice');

                foreach ($units as $key => $unit) {
                    $idx = $key + 1;
                    $i=$key+2;

                    if($unit->planning_date != null && $unit->date_invoice != null) {
                        $planning_date = Carbon::parse($unit->planning_date);
                        $date_invoice = Carbon::parse($unit->date_invoice);
                        if($date_invoice->gt($planning_date)) {
                            $sheet->cells("A$i:H$i", function($cells){
                                $cells->setFontWeight('bold')->setFontColor('#ff0000');
                            });                 
                        }
                    }

                    $sheet->SetCellValue('A'.$i,$idx);
                    $sheet->SetCellValue('B'.$i,$unit->job_order_code);
                    $sheet->SetCellValue('C'.$i,fullDate($unit->created_at));
                    $sheet->SetCellValue('D'.$i,$unit->invoice_code);
                    $sheet->SetCellValue('E'.$i,$unit->creator);
                    $sheet->SetCellValue('F'.$i,fullDate($unit->finished_date));
                    $sheet->SetCellValue('G'.$i,fullDate($unit->planning_date));
                    $sheet->SetCellValue('H'.$i,fullDate($unit->date_invoice));
                }

                // $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');
    }

    public function report_13($request)
    {
        $units = $this->getInvoiceValidationReport($request);
        
        Excel::create('Laporan KPI Validasi Invoice - '.Carbon::now(), function($excel) use ($units) {
            $excel->sheet('Laporan KPI Validasi Invoice', function($sheet) use ($units){
                // STYLING ----------------------
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:J1', function($cells){
                    $cells->setFontWeight('bold');
                });

                $sheet->SetCellValue('A1','No');
                $sheet->SetCellValue('B1','No Invoice');
                $sheet->SetCellValue('C1','Tanggal Invoice');
                $sheet->SetCellValue('D1','Dibuat Oleh');
                $sheet->SetCellValue('F1','Jumlah Edit Invoice');
                $sheet->SetCellValue('G1','Jumlah Batal Posting');

                foreach ($units as $key => $unit) {
                    $idx = $key + 1;
                    $i=$key+2;

                    $sheet->SetCellValue('A'.$i,$idx);
                    $sheet->SetCellValue('B'.$i,$unit->code);
                    $sheet->SetCellValue('C'.$i,fullDate($unit->created_at));
                    $sheet->SetCellValue('D'.$i,$unit->creator);
                    $sheet->SetCellValue('E'.$i,$unit->qty_edit);
                    $sheet->SetCellValue('F'.$i,$unit->qty_batal_posting);
                }

                // $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');
    }

    public function report_11($request)
    {

        $wr = "1=1 and jo.id is not null";

        if ($request->start_date && $request->end_date) {
            $startDate=Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate=Carbon::parse($request->end_date)->format('Y-m-d');
            $wr.=" and wo.date between '$startDate' and '$endDate'";
        }

        if ($request->customer_id) {
            $wr.=" and wo.customer_id = $request->customer_id";
        }

        if ($request->service_id) {
            $wr.=" and sg.id = $request->service_id";
        }

        if ($request->company_id) {
            $wr.=" and wo.company_id = $request->company_id";
        }

        $data = DB::table('work_orders as wo')
        ->leftJoin('work_order_details as wod', 'wo.id', 'wod.header_id')
        ->leftJoin('quotation_details as qd', 'wod.quotation_detail_id', 'qd.id')
        ->leftJoin('impositions as imp', 'imp.id', 'qd.imposition')
        ->leftJoin('pieces as p', 'p.id', 'qd.piece_id')
        ->leftJoin('services as s', 's.id', 'qd.service_id')
        ->leftJoin('service_groups as sg', 'sg.id', 's.service_group_id')
        ->leftJoin('contacts as c', 'wo.customer_id', 'c.id')
        ->leftJoin('job_orders as jo','jo.work_order_detail_id','wod.id')
        ->leftJoin('job_order_details as jod','jod.header_id','jo.id')
        ->leftJoin('job_order_costs as joc','joc.header_id','jo.id')
        ->whereRaw($wr)
        ->selectRaw("
            wo.*,
            if(jo.service_type_id in (2,3,4),sum(jo.total_unit),jod.qty) as qty,
            c.name AS customer_name,
            sg.name AS service_name,
            sum(joc.total_price) as cost,
            sg.id as service_group_id,
            group_concat(joc.id) as joc_list,
            if(qd.service_type_id in (6,7),p.name,if(qd.service_type_id=2,'Kontainer',if(qd.service_type_id=3,'Unit',imp.name))) as imposition_name
            ")->groupBy('wo.id','sg.id')->orderBy('wo.date','desc')->get();

        Excel::create('Laporan Operasional FF - '.Carbon::now(), function($excel) use ($data) {
            $excel->sheet('Data', function($sheet) use ($data){
// STYLING ----------------------
                $sheet->setStyle([
                    'font' => [
                        'name' => 'Calibri',
                        'size' => 11,
                    ],
                    'fill' => [
                        'type'  => PHPExcel_Style_Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFFFF']
                    ]
                ]);
                $sheet->cells('A1:J1', function($cells){
                    $cells->setFontWeight('bold');
                });
                $sheet->setColumnFormat([
                    'J' => '#,##0.00',
                    'H' => '#,##0.00'
                ]);
// END STYLING -------------------
                $sheet->SetCellValue('A1','No');
                $sheet->SetCellValue('B1','No WO');
                $sheet->SetCellValue('C1','Tanggal');
                $sheet->SetCellValue('D1','Customer');
                $sheet->SetCellValue('E1','No AJU');
                $sheet->SetCellValue('F1','No BL');
                $sheet->SetCellValue('G1','Layanan');
                $sheet->SetCellValue('H1','Qty');
                $sheet->SetCellValue('I1','Satuan');
                $sheet->SetCellValue('J1','Biaya Operasional');

                foreach ($data as $key => $value) {
                    $idx = $key + 1;
                    $i=$key+2;
                    $sheet->SetCellValue('A'.$i,$idx);
                    $sheet->SetCellValue('B'.$i,$value->code);
                    $sheet->SetCellValue('C'.$i,$value->date);
                    $sheet->SetCellValue('D'.$i,$value->customer_name);
                    $sheet->SetCellValue('E'.$i,$value->aju_number);
                    $sheet->SetCellValue('F'.$i,$value->no_bl);
                    $sheet->SetCellValue('G'.$i,$value->service_name);
                    $sheet->SetCellValue('H'.$i,$value->qty);
                    $sheet->SetCellValue('I'.$i,$value->imposition_name);
                    $sheet->SetCellValue('J'.$i,$value->cost);
                }

// $sheet->setAutoFilter('K2:K'.$i);

            });
        })->export('xls');
    }

    public function preview_11($request)
    {
        $wr = "1=1 and jo.id is not null";

        if ($request->start_date && $request->end_date) {
            $startDate=Carbon::parse($request->start_date)->format('Y-m-d');
            $endDate=Carbon::parse($request->end_date)->format('Y-m-d');
            $wr.=" and wo.date between '$startDate' and '$endDate'";
        }

        if ($request->customer_id) {
            $wr.=" and wo.customer_id = $request->customer_id";
        }

        if ($request->service_id) {
            $wr.=" and sg.id = $request->service_id";
        }

        if ($request->company_id) {
            $wr.=" and wo.company_id = $request->company_id";
        }

        $data = DB::table('work_orders as wo')
        ->leftJoin('work_order_details as wod', 'wo.id', 'wod.header_id')
        ->leftJoin('quotation_details as qd', 'wod.quotation_detail_id', 'qd.id')
        ->leftJoin('impositions as imp', 'imp.id', 'qd.imposition')
        ->leftJoin('pieces as p', 'p.id', 'qd.piece_id')
        ->leftJoin('services as s', 's.id', 'qd.service_id')
        ->leftJoin('service_groups as sg', 'sg.id', 's.service_group_id')
        ->leftJoin('contacts as c', 'wo.customer_id', 'c.id')
        ->leftJoin('job_orders as jo','jo.work_order_detail_id','wod.id')
        ->leftJoin('job_order_details as jod','jod.header_id','jo.id')
        ->leftJoin('job_order_costs as joc','joc.header_id','jo.id')
        ->whereRaw($wr)
        ->selectRaw("
            wo.*,
            if(jo.service_type_id in (2,3,4),sum(jo.total_unit),jod.qty) as qty,
            c.name AS customer_name,
            sg.name AS service_name,
            sum(joc.total_price) as cost,
            sg.id as service_group_id,
            group_concat(joc.id) as joc_list,
            if(qd.service_type_id in (6,7),p.name,if(qd.service_type_id=2,'Kontainer',if(qd.service_type_id=3,'Unit',imp.name))) as imposition_name
            ")
        ->groupBy('wo.id','sg.id')
        ->orderBy('wo.date','desc')->get();
        $resp['units'] = $data;
        return view('operational_report/operational_ff', $resp);
    }

    /*
      Date : 12-03-2020
      Description : Mengambil data KPI Invoice
      Developer : Didin
      Status : Create
    */
    public function getInvoiceReport($request) {
        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date = Carbon::parse($request->end_date)->format('Y-m-d');
        $invoice = DB::table('job_orders AS J')
        ->leftJoin('invoices AS I', 'I.id', 'J.invoice_id')
        ->leftJoin('users AS U', 'U.id', 'J.create_by')
        ->leftJoin('job_order_details AS JD', 'JD.header_id', 'J.id')
        ->leftJoin('manifest_details AS MD', 'MD.job_order_detail_id', 'JD.id')
        ->leftJoin('manifests AS M', 'M.id', 'MD.header_id')
        ->leftJoin('containers AS C', 'C.id', 'M.container_id')
        ->leftJoin('voyage_schedules AS V', 'V.id', 'C.voyage_schedule_id')
        ->whereRaw('DATE_FORMAT(J.created_at, "%Y-%m-%d") BETWEEN "' . $start_date . '" AND "' . $end_date . '"')
        ->selectRaw('I.code AS invoice_code, 
        J.code AS job_order_code, 
        U.name AS creator, 
        J.created_at,
        I.created_at AS date_invoice,
        (IF(J.service_type_id = 2, V.departure, (SELECT date_update FROM kpi_logs JOIN kpi_statuses ON kpi_logs.kpi_status_id = kpi_statuses.id WHERE is_done = 1 AND job_order_id = J.id GROUP BY job_order_id) )) AS finished_date,
        DATE_ADD((IF(J.service_type_id = 2, V.departure, (SELECT date_update FROM kpi_logs JOIN kpi_statuses ON kpi_logs.kpi_status_id = kpi_statuses.id WHERE is_done = 1 AND job_order_id = J.id GROUP BY job_order_id) )), INTERVAL 2 DAY) AS planning_date')
        ->groupBy('J.id')
        ->get();

        return $invoice;
    }

    /*
      Date : 12-03-2020
      Description : Mengambil data KPI Validasi Invoice
      Developer : Didin
      Status : Create
    */
    public function getInvoiceValidationReport($request) {
        $start_date = Carbon::parse($request->start_date)->format('Y-m-d');
        $end_date = Carbon::parse($request->end_date)->format('Y-m-d');
        $invoice = DB::table('invoices AS I')
        ->leftJoin('users AS U', 'I.create_by', 'U.id')
        ->whereRaw('DATE_FORMAT(I.created_at, "%Y-%m-%d") BETWEEN "' . $start_date . '" AND "' . $end_date . '"')
        ->select('U.name AS creator', 'I.created_at', 'qty_edit', 'qty_batal_posting', 'I.code')
        ->get();
        return $invoice;
    }

    public function preview_12(Request $request)
    {
        $resp['units'] = $this->getInvoiceReport($request);
        return view('operational_report/invoice_report', $resp);
    }

    public function preview_13(Request $request)
    {
        $resp['units'] = $this->getInvoiceValidationReport($request);
        return view('operational_report/invoice_validation_report', $resp);
    }

    public function export_bdv(Request $request,$id)
    {
        $data['informasi_saldo'] = DB::select("SELECT accounts.code, accounts.name, detail.jmldebet, detail.jmlkredit
            FROM accounts
            LEFT JOIN (
            SELECT journal_details.account_id, SUM(IF(accounts.Jenis = 1, journal_details.debet-journal_details.credit, NULL)) AS jmlDebet, SUM(IF(accounts.Jenis = 2, journal_details.credit-journal_details.debet, NULL)) AS jmlKredit
            FROM journal_details
            LEFT JOIN accounts ON journal_details.account_id = accounts.id
            JOIN journals on journal_details.header_id = journals.id
            WHERE journals.company_id =( SELECT id FROM companies WHERE is_pusat = 1 limit 1)
            GROUP BY journal_details.account_id )detail ON accounts.id = detail.account_id
            WHERE accounts.id =( SELECT cash_account_id FROM companies WHERE is_pusat = 1 limit 1) OR accounts.id = ( SELECT bank_account_id FROM companies WHERE is_pusat = 1 limit 1)
            ORDER BY accounts.code ASC");
        $data['cost_detail']=\App\Model\JobOrderCost::with('cost_type','vendor')->where('header_id', $id)->get();
// return view('export.bdv');
// dd($data['cost_detail']->first());

        return PDF::loadView('export.bdv', $data)->stream();
    }

    public function show_cost(Request $request)
    {
        $item=DB::table('job_order_costs')
        ->leftJoin('cost_types','cost_types.id','job_order_costs.cost_type_id')
        ->leftJoin('contacts','contacts.id','job_order_costs.vendor_id')
        ->whereRaw("job_order_costs.id in ($request->list)")
        ->selectRaw('
            job_order_costs.price,
            job_order_costs.qty,
            job_order_costs.total_price,
            job_order_costs.description,
            contacts.name as vendor,
            cost_types.name
            ')->get();

        return Response::json($item,200,[],JSON_NUMERIC_CHECK);
    }
}
