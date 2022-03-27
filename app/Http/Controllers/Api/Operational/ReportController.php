<?php

namespace App\Http\Controllers\Api\Operational;

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
use DB;
use Response;
use PHPExcel_Style_Fill;

class ReportController extends Controller
{
  public function index()
  {
    $data['company']=companyAdmin(auth()->id());
    $data['service']=Service::with('service_type')->get();
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
    return PDF::loadView('pdf.shipment_instructions',$data)->setPaper('f4','potrait')->stream('shipment_instruction.pdf');

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

  public function report_11($request)
  {
    
    $data = DB::table('work_orders as wo')
    ->leftJoin('work_order_details as wod', 'wo.id', 'wod.header_id')
    ->leftJoin('quotation_details as qd', 'wod.quotation_detail_id', 'qd.id')
    ->leftJoin('pieces as p', 'p.id', 'qd.piece_id')
    ->leftJoin('services as s', 's.id', 'qd.service_id')
    ->leftJoin('contacts as c', 'wo.customer_id', 'c.id');

    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';

    $data = $start_date != '' && $end_date != '' ? $date->whereBetween('date', [$start_date, $end_date]) : $data;

    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : ''; 
    $data = $customer_id != '' ? $data->where('wo.customer_id', $customer_id) : $data;

    $service_id = $request->service_id;
    $service_id = $service_id != null ? $service_id : ''; 
    $data = $service_id != '' ? $data->where('qd.service_id', $service_id) : $data;
    

    $data = $data->orderBy('date', 'desc')->selectRaw('wo.*, wod.qty AS qty, p.name AS piece_name, c.name AS customer_name, s.name AS service_name, qd.cost as cost')->get();

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
        $sheet->SetCellValue('I1','Satuan( Pengenaan )');
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
          $sheet->SetCellValue('I'.$i,$value->piece_name);
          $sheet->SetCellValue('J'.$i,$value->cost);
        }

        // $sheet->setAutoFilter('K2:K'.$i);

      });
    })->export('xls');
  }

  public function preview_11($request)
  {
    
    $data = DB::table('work_orders as wo')
    ->leftJoin('work_order_details as wod', 'wo.id', 'wod.header_id')
    ->leftJoin('quotation_details as qd', 'wod.quotation_detail_id', 'qd.id')
    ->leftJoin('pieces as p', 'p.id', 'qd.piece_id')
    ->leftJoin('services as s', 's.id', 'qd.service_id')
    ->leftJoin('contacts as c', 'wo.customer_id', 'c.id');

    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';

    $data = $start_date != '' && $end_date != '' ? $date->whereBetween('date', [$start_date, $end_date]) : $data;

    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : ''; 
    $data = $customer_id != '' ? $data->where('wo.customer_id', $customer_id) : $data;

    $service_id = $request->service_id;
    $service_id = $service_id != null ? $service_id : ''; 
    $data = $service_id != '' ? $data->where('qd.service_id', $service_id) : $data;
    

    $data = $data->orderBy('date', 'desc')->selectRaw('wo.*, wod.qty AS qty, p.name AS piece_name, c.name AS customer_name, s.name AS service_name, qd.cost as cost')->get();

    $resp['units'] = $data;

    return view('operational_report/operational_ff', $resp);
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
                          WHERE journals.company_id =( SELECT id FROM companies WHERE is_pusat = 1)
                          GROUP BY journal_details.account_id )detail ON accounts.id = detail.account_id
                        WHERE accounts.id =( SELECT cash_account_id FROM companies WHERE is_pusat = 1) OR accounts.id = ( SELECT bank_account_id FROM companies WHERE is_pusat = 1)
                        ORDER BY accounts.code ASC");
    $data['cost_detail']=\App\Model\JobOrderCost::with('cost_type','vendor')->where('header_id', $id)->get();
    // return view('export.bdv');
    // dd($data['cost_detail']->first());
    return PDF::loadView('export.bdv', $data)->stream();
  }
}
