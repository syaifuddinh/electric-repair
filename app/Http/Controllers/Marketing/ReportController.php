<?php

namespace App\Http\Controllers\Marketing;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Company;
use App\Model\Service;
use App\Model\Contact;
use Excel;
use DB;
use Response;
use Carbon\Carbon;
use DataTables;

class ReportController extends Controller
{
  protected $service_type = [
    1 => 'PENGIRIMAN LCL',
    2 => 'PENGIRIMAN FCL',
    3 => 'PENGIRIMAN PER TRIP',
    4 => 'TRANSPORTASI',
    5 => 'SEWA GUDANG',
    6 => 'JASA KEPABEANAN',
    7 => 'JASA LAINNYA',
  ];
  protected $month = [
    1 => 'JAN',
    2 => 'FEB',
    3 => 'MAR',
    4 => 'APR',
    5 => 'MEI',
    6 => 'JUN',
    7 => 'JUL',
    8 => 'AGS',
    9 => 'SEP',
    10 => 'OKT',
    11 => 'NOV',
    12 => 'DES',
  ];
  public function index()
  {
    $data['company']=companyAdmin(auth()->id());
    $data['services']=Service::with('service_type')->get();
    $data['customer']=DB::table('contacts')->where('is_pelanggan', 1)->selectRaw('id,name')->get();
    $data['service_group']=DB::table('service_groups')->get();
    $data['month']=$this->month;

    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  /*
    Date		: 9 Maret 2020
    Description	: Add query get all service_types 
    Developer	: Dimas
    Status		: Edit
  */
  public function activity_wo_index($value='')
  {    
    $data['service_types'] = DB::table('service_types')->get();
    
    $data['service'] = DB::table('services')
    ->leftJoin('service_types','service_types.id','=','services.service_type_id')
    ->select(['services.*','service_types.name as service_type'])
    ->get();

    return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
  }

  public function export(Request $request)
  {
    $rtype=$request->report_id;
    switch ($rtype) {
      case 1:
        $return=$this->report_1($request);
        break;
      case 2:
        $return=$this->report_2($request);
        break;
      case 3:
        $return=$this->report_3($request);
        break;
      case 4:
        $return=$this->report_4($request);
        break;

      default:
        $return=null;
        break;
    }
    return $return;
  }

  public function report_1($request)
  {
    Excel::create("Laporan Executive Summary", function($excel) use ($request) {
      $excel->sheet("Data", function($sheet) use ($request){
        //data 1
        //layanan 1 - 4
        $wr="1=1";

        if ($request->company_id) {
          $wr.=" AND job_orders.company_id = $request->company_id";
          $company = Company::find($request->company_id);
          if($company->name) {
            $sheet->cell('A2',function($cell) use ($company){
                $cell->setValue("{$company->name}");
                $cell->setFontSize('12');
                $cell->setFontWeight('bold');
            });
          }
        } else {
            $sheet->cell('A2',function($cell){
                $cell->setValue("Semua Cabang");
                $cell->setFontSize('12');
                $cell->setFontWeight('bold');
            });
        }

        if ($request->customer_id) {
          $wr.=" AND job_orders.customer_id = $request->customer_id";
          $customer = Contact::find($request->customer_id);
          if($customer->name) {
            $sheet->cell('A3',function($cell) use ($customer){
                $cell->setValue($customer->name);
                $cell->setFontSize('12');
                $cell->setFontWeight('bold');
            });
          }
        } else {
            $sheet->cell('A3',function($cell){
                $cell->setValue("Semua Customer");
                $cell->setFontSize('12');
                $cell->setFontWeight('bold');
            });
        }

        if ($request->start_date && $request->end_date) {
          $start=Carbon::parse($request->start_date)->format('Y-m-d');
          $end=Carbon::parse($request->end_date)->format('Y-m-d');
          $wr.=" and job_orders.shipment_date between '$start' and '$end'";

          $sheet->cell('A4',function($cell) use($start, $end){
            $cell->setValue("Periode {$start} s/d {$end}");
            $cell->setFontSize('12');
            $cell->setFontWeight('bold');
          });
        } else {
            $sheet->cell('A4',function($cell){
                $cell->setValue("Semua Periode");
                $cell->setFontSize('12');
                $cell->setFontWeight('bold');
            });
        }

        $sheet->cell('A1',function($cell){
          $cell->setValue('EXECUTIVE SUMMARY');
          $cell->setFontSize('16');
          $cell->setFontWeight('bold');
          $cell->setAlignment('center');
        });

        $sheet->mergeCells('A1:E1');
        $sheet->cell('A1', function($cell){
            $cell->setAlignment('center');
            $cell->setValignment('center');
        });
        $sheet->setHeight(1, 30);

        $sheet->mergeCells('A2:E2');
        $sheet->cell('A2', function($cell){
            $cell->setAlignment('center');
            $cell->setValignment('center');
        });
        $sheet->setHeight(2, 20);

        $sheet->mergeCells('A3:E3');
        $sheet->cell('A3', function($cell){
            $cell->setAlignment('center');
            $cell->setValignment('center');
        });
        $sheet->setHeight(3, 20);

        $sheet->mergeCells('A4:E4');
        $sheet->cell('A4', function($cell){
            $cell->setAlignment('center');
            $cell->setValignment('center');
        });
        $sheet->setHeight(4, 20);

        $label1="A";
        $value1="B";
        $cell1=6;
        $cell2=6;
        $group=DB::table('service_groups')->whereIn('id',[1,2,3])->orderBy('id','asc')->get();
        foreach ($group as $key => $value) {
          //$data=DB::table('job_order_costs')
          //->leftJoin('job_orders','job_orders.id','=','job_order_costs.header_id')
          //->leftJoin('work_orders','work_orders.id','=','job_orders.work_order_id')
          //->leftJoin('services','services.id','=','job_orders.service_id')
          //->whereRaw($wr)
          //->where('services.service_group_id', $value->id)
          //->select([
          //  DB::raw("count(DISTINCT job_orders.id) as job_order_total"),
          //  DB::raw("count(DISTINCT work_orders.id) as work_order_total"),
          //  DB::raw("IFNULL(SUM(DISTINCT job_orders.total_unit),0) as qty_total"),
          //  DB::raw("IFNULL(SUM(DISTINCT job_orders.total_price),0) as pendapatan_total"),
          //  DB::raw("IFNULL(SUM(IF(job_order_costs.type=1,job_order_costs.total_price,0)),0) as biaya_total"),
          //])->first();
          $data = DB::table('job_orders')
            ->leftJoin('services','services.id','=','job_orders.service_id')
            ->leftJoin(DB::raw('(select sum(total_price) as biaya,header_id from job_order_costs where type = 1 group by header_id) as Y'),'Y.header_id','job_orders.id')
            ->leftJoin(DB::raw('(select sum(qty) as qty_detail,header_id from job_order_details group by header_id) as X'),'X.header_id','job_orders.id')
            ->selectRaw('
                count(job_orders.id) as job_order_total,
                count(distinct job_orders.work_order_id) as work_order_total,
                ifnull(sum(job_orders.total_price),0) as pendapatan_total,
                ifnull(sum(Y.biaya),0) as biaya_total,
                ifnull(sum(if(services.service_type_id in (2,3,4),job_orders.total_unit,X.qty_detail)),0) as qty_total')
            ->whereRaw($wr)
            ->where('services.service_group_id', $value->id)
            ->first();
          // dd($this->service_type[$i]);
          $sheet->cell($label1.$cell1,function($cell){
            $cell->setFontWeight('bold');
          });
          $sheet->SetCellValue($label1.$cell1++,$value->name);
          $cell2++;
          $sheet->SetCellValue($label1.$cell1++,'JUMLAH WO');
          $sheet->SetCellValue($label1.$cell1++,'JUMLAH JO');
          $sheet->SetCellValue($label1.$cell1++,'QTY');
          $sheet->SetCellValue($label1.$cell1++,'TOTAL PENDAPATAN');
          $sheet->SetCellValue($label1.$cell1++,'TOTAL BIAYA');
          $sheet->SetCellValue($label1.$cell1++,'TOTAL KEUNTUNGAN');
          $cell1++;

          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->work_order_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->job_order_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->qty_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->pendapatan_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->biaya_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->pendapatan_total-$data->biaya_total));
          $cell2++;

        }

        $label1="D";
        $value1="E";
        $cell1=6;
        $cell2=6;
        $group=DB::table('service_groups')->whereIn('id',[4,5,6])->orderBy('id','asc')->get();
        foreach ($group as $key => $value) {
          //$data=DB::table('job_order_costs')
          //->leftJoin('job_orders','job_orders.id','=','job_order_costs.header_id')
          //->leftJoin('work_orders','work_orders.id','=','job_orders.work_order_id')
          //->leftJoin('services','services.id','=','job_orders.service_id')
          //->whereRaw($wr)
          //->where('services.service_group_id', $value->id)
          //->select([
          //  DB::raw("count(DISTINCT job_orders.id) as job_order_total"),
          //  DB::raw("count(DISTINCT work_orders.id) as work_order_total"),
          //  DB::raw("IFNULL(SUM(DISTINCT job_orders.total_unit),0) as qty_total"),
          //  DB::raw("IFNULL(SUM(DISTINCT job_orders.total_price),0) as pendapatan_total"),
          //  DB::raw("IFNULL(SUM(IF(job_order_costs.type=1,job_order_costs.total_price,0)),0) as biaya_total"),
          //])->first();
          $data = DB::table('job_orders')
            ->leftJoin('services','services.id','=','job_orders.service_id')
            ->leftJoin(DB::raw('(select sum(total_price) as biaya,header_id from job_order_costs where type = 1 group by header_id) as Y'),'Y.header_id','job_orders.id')
            ->leftJoin(DB::raw('(select sum(qty) as qty_detail,header_id from job_order_details group by header_id) as X'),'X.header_id','job_orders.id')
            ->selectRaw('
                count(job_orders.id) as job_order_total,
                count(distinct job_orders.work_order_id) as work_order_total,
                ifnull(sum(job_orders.total_price),0) as pendapatan_total,
                ifnull(sum(Y.biaya),0) as biaya_total,
                ifnull(sum(if(services.service_type_id in (2,3,4),job_orders.total_unit,X.qty_detail)),0) as qty_total')
            ->whereRaw($wr)
            ->where('services.service_group_id', $value->id)
            ->first();
          // dd($this->service_type[$i]);
          $sheet->cell($label1.$cell1,function($cell){
            $cell->setFontWeight('bold');
          });
          $sheet->SetCellValue($label1.$cell1++,$value->name);
          $cell2++;
          $sheet->SetCellValue($label1.$cell1++,'JUMLAH WO');
          $sheet->SetCellValue($label1.$cell1++,'JUMLAH JO');
          $sheet->SetCellValue($label1.$cell1++,'QTY');
          $sheet->SetCellValue($label1.$cell1++,'TOTAL PENDAPATAN');
          $sheet->SetCellValue($label1.$cell1++,'TOTAL BIAYA');
          $sheet->SetCellValue($label1.$cell1++,'TOTAL KEUNTUNGAN');
          $cell1++;

          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->work_order_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->job_order_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->qty_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->pendapatan_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->biaya_total));
          $sheet->SetCellValue($value1.$cell2++,formatNumber($data->pendapatan_total-$data->biaya_total));
          $cell2++;
        }
        $sheet->setStyle([
          'font' => [
            'name' => 'Calibri',
            'size' => 11,
          ],
        ]);
      });
    })->download('xls');
  }

  public function report_2($request)
  {
    Excel::create("Laporan Marketing Tahunan", function($excel) use ($request) {
      $excel->sheet("Data", function($sheet) use ($request){
        //data 1
        //layanan 1 - 4
        $wr="1=1";
        if ($request->company_id) {
          $wr.=" AND company_id = $request->company_id";
        }
        if ($request->year) {
          $wr.=" AND year(created_at) = '$request->year'";
        }
        $sheet->SetCellValue('A2','BULAN');
        $sheet->SetCellValue('B2','LEAD');
        $sheet->SetCellValue('C2','LEAD FAILED');
        $sheet->SetCellValue('D2','OPPORTUNITY');
        $sheet->SetCellValue('E2','OPPORTUNITY FAILED');
        $sheet->SetCellValue('F2','INQUERY');
        $sheet->SetCellValue('G2','INQUERY FAILED');
        $sheet->SetCellValue('H2','QUOTATION');
        $sheet->SetCellValue('I2','QUOTATION FAILED');
        $sheet->SetCellValue('J2','WORK ORDER');

        $cell1="A";
        $cell2="B";
        $cell3="C";
        $cell4="D";
        $cell5="E";
        $cell6="F";
        $cell7="G";
        $cell8="H";
        $cell9="I";
        $cell10="J";
        $urut=3;
        for ($i=1; $i <= 12; $i++) {
          $lead=DB::table('leads')
          ->whereRaw($wr." AND month(created_at) = '$i'")
          ->select([
            DB::raw("count(*) as lead"),
            DB::raw("SUM(IF(step = 6,1,0)) as lead_failed"),
          ])
          ->first();
          $opportunity_inquery=DB::table('inqueries')
          ->whereRaw($wr." AND month(created_at) = '$i'")
          ->select([
            DB::raw("SUM(IF(cancel_opportunity_by is null,1,0)) as opportunity_success"),
            DB::raw("SUM(IF(cancel_opportunity_by is not null,1,0)) as opportunity_failed"),
            DB::raw("SUM(IF(cancel_inquery_by is null,1,0)) as inquery_success"),
            DB::raw("SUM(IF(cancel_inquery_by is not null,1,0)) as inquery_failed"),
          ])
          ->first();
          $quot=DB::table('quotations')
          ->whereRaw($wr." AND month(created_at) = '$i'")
          ->select([
            DB::raw("count(*) as quotation"),
            DB::raw("SUM(IF(status_approve = 5,1,0)) as quotation_failed"),
          ])
          ->first();
          $wo=DB::table('work_orders')
          ->whereRaw($wr." AND month(created_at) = '$i'")
          ->select([
            DB::raw("count(*) as wo"),
          ])
          ->first();

          $sheet->SetCellValue($cell1.$urut,$this->month[$i]);
          $sheet->SetCellValue($cell2.$urut,$lead->lead??0);
          $sheet->SetCellValue($cell3.$urut,$lead->lead_failed??0);
          $sheet->SetCellValue($cell4.$urut,$opportunity_inquery->opportunity_success??0);
          $sheet->SetCellValue($cell5.$urut,$opportunity_inquery->opportunity_failed??0);
          $sheet->SetCellValue($cell6.$urut,$opportunity_inquery->inquery_success??0);
          $sheet->SetCellValue($cell7.$urut,$opportunity_inquery->inquery_failed??0);
          $sheet->SetCellValue($cell8.$urut,$quot->quotation??0);
          $sheet->SetCellValue($cell9.$urut,$quot->quotation_failed??0);
          $sheet->SetCellValue($cell10.$urut,$wo->wo??0);
          $urut++;
          // dd($opportunity_inquery);
        }
        $sheet->setStyle([
          'font' => [
            'name' => 'Calibri',
            'size' => 11,
          ],
        ]);
        $sheet->cells('A2:J2', function($cells){
          $cells->setFontWeight('bold');
        });
        $sheet->cells('A3:A14', function($cells){
          $cells->setFontWeight('bold');
        });
      });
    })->download('xls');
  }

  public function report_3($request)
  {
    // dd($request);freport_3
    $start_date = $request->start_date;
    $start_date = $start_date != '' ? preg_replace('/([0-9]+)-([0-9]+)-([0-9]+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != '' ? preg_replace('/([0-9]+)-([0-9]+)-([0-9]+)/', '$3-$2-$1', $end_date) : '';
    $periode = $start_date != '' && $end_date != '' ? $request->start_date . ' - ' . $request->end_date : 'Semua Periode';
    Excel::create("Laporan Freight Forwarding $periode", function($excel) use ($request, $start_date, $end_date) {
      $excel->sheet("Data", function($sheet) use ($request, $start_date, $end_date){
        //data 1
        //layanan 1 - 4
        $sheet->SetCellValue('A2','NO');
        $sheet->SetCellValue('B2','Customer');
        $sheet->SetCellValue('C2','No WO');
        $sheet->SetCellValue('D2','Tanggal');
        $sheet->SetCellValue('E2','Layanan ');
        $sheet->SetCellValue('F2','QTY');
        $sheet->SetCellValue('G2','Satuan ');
        $sheet->SetCellValue('H2','Pendapatan');
        $sheet->SetCellValue('I2','Biaya Operasional');
        $sheet->SetCellValue('J2','Biaya Reimburse');
        $sheet->SetCellValue('K2','Profit');
        $sheet->SetCellValue('L2','Persentase');

        $sheet->setColumnFormat([
          'F' => '0',
          'G' => '#,##0.00',
          'H' => '#,##0.00',
          'I' => '#,##0.00',
          'J' => '#,##0.00',
          'K' => '#,##0.00',
          'L' => '#,##0'
        ]);


        $cell1="A";
        $cell2="B";
        $cell3="C";
        $cell4="D";
        $cell5="E";
        $cell6="F";
        $cell7="G";
        $cell8="H";
        $cell9="I";
        $cell10="J";
        $cell11="K";
        $cell12="L";
        $urut=3;

    $service_id = $request->service_group_id;
    $service_id = $service_id != '' ? $service_id : '';

    $wr="1=1";
    if ($request->customer_id) {
      $wr.= " AND jo.customer_id = $request->customer_id";
    }
    if ($request->company_id) {
      $wr.= " AND wo.company_id = $request->company_id";
    }
    if ($request->service_id) {
      $wr.= " AND sg.id = $request->service_id";
    }
    if ($request->start_date && $request->end_date) {
      $start = Carbon::parse($request->start_date)->format('Y-m-d');
      $end = Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" AND wo.date between '$start' and '$end'";
    }

    // if ($request->customer_id) {
    //   $wr.=" AND work_orders.customer_id = $request->customer_id";
    // }
    // if ($request->start_date && $request->end_date) {
    //   $start=Carbon::parse($request->start_date)->format('Y-m-d');
    //   $end=Carbon::parse($request->end_date)->format('Y-m-d');
    //   $wr.=" and date(work_orders.created_at) between '$start' and '$end'";
    // }
    $sql="
    select
    date(work_orders.date) as date_wo,
    contacts.name as customer_name,
    sum(ifnull((select sum(total_price) from job_order_costs where header_id = jo.id and type = 1 and status in (3,5,8)),0)) as operational_price,
    sum(ifnull((select sum(total_price) from job_order_costs where header_id = jo.id and type = 2 and status in (3,5,8)),0)) as talangan_price,
    sum(total_price) as invoice_price,
    work_orders.code as code_wo,
    service_groups.name as service_name,
    max(X.satuan) as pieces_name,
    if(X.stype in (6,7),1,sum(X.qty)) as qty,
    contacts.name as customer
    from job_orders as jo
    left join contacts on contacts.id = jo.customer_id
    left join work_orders on work_orders.id = jo.work_order_id
    left join work_order_details on work_order_details.id = jo.work_order_detail_id
    left join services on services.id = jo.service_id
    left join service_groups on service_groups.id = services.service_group_id
    left join service_types on service_types.id = jo.service_type_id
    left join (select job_orders.id, job_orders.service_type_id as stype, if(job_orders.service_type_id=2,'Kontainer',if(job_orders.service_type_id=3,'Unit',if(job_orders.service_type_id in (6,7),pieces.name,if(quotation_details.imposition=1,'Kubikasi',if(quotation_details.imposition=2,'Tonase','Item'))))) as satuan, IF(job_orders.service_type_id in (2,3,4),max(job_orders.total_unit),sum(jod.qty)) as qty from job_orders left join (select sum(qty) as qty, header_id from job_order_details group by header_id) jod on jod.header_id = job_orders.id left join quotation_details on quotation_details.id = job_orders.quotation_detail_id left join pieces on quotation_details.piece_id = pieces.id group by job_orders.id) X on X.id = jo.id
    where $wr
    group by services.service_group_id, jo.work_order_id
    order by work_orders.date desc, work_orders.id asc
    ";
    // $item = DB::select($sql);
    $wr.=" and sg.id is not null";
    $item = DB::table('work_orders as wo')
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
    // ->leftJoin(DB::raw('(select sum(job_order_costs.total_price) as total_price, group_concat(distinct job_order_costs.id) as joc_list, job_orders.work_order_detail_id from job_order_costs left join job_orders on job_orders.id = job_order_costs.header_id where job_order_costs.status in(3,5,8) group by job_orders.work_order_detail_id) as ops'),'ops.work_order_detail_id','wod.id')
    ->whereRaw($wr)
    ->selectRaw("
      wo.date as date_wo,
      wo.code as code_wo,
      sum(if(joc.type=1,joc.total_price,0)) as operational_price,
      sum(if(joc.type=2,joc.total_price,0)) as talangan_price,
      sum(jo.total_price) as invoice_price,
      if(max(jo.service_type_id) in (2,3,4),max(jo.total_unit),if(max(jo.service_type_id) in (6),count(distinct jo.id),jod.qty)) as qty,
      c.name AS customer_name,
      c.name AS customer,
      sg.name AS service_name,
      sum(joc.total_price) as cost,
      sg.id as service_group_id,
      if(qd.service_type_id in (6,7),p.name,if(qd.service_type_id=2,'Kontainer',if(qd.service_type_id=3,'Unit',imp.name))) as pieces_name
    ")
    ->groupBy('wo.id','sg.id')
    ->havingRaw('invoice_price > 0')
    ->orderBy('wo.date','desc')
    ->orderBy('wo.created_at','desc')
    ->get();

    // $result = DataTables::of($item)
    //   ->editColumn('date_wo', function($item){
    //     return dateView($item->date_wo);
    //   })
    //   ->editColumn('operational_price', function($item){
    //     return formatNumber($item->operational_price);
    //   })
    //   ->editColumn('talangan_price', function($item){
    //     return formatNumber($item->talangan_price);
    //   })
    //   ->editColumn('invoice_price', function($item){
    //     return formatNumber($item->invoice_price);
    //   })
    //   ->editColumn('profit', function($item){
    //     return formatNumber($item->profit);
    //   })
    //   ->editColumn('presentase', function($item){
    //     return formatNumber($item->presentase).' %';
    //   })
    //   // ->editColumn('status', function($item){
    //   //   $stt=[
    //   //     1 => '<span class="badge badge-warning">Diajukan</span>',
    //   //     2 => '<span class="badge badge-success">Disetujui</span>',
    //   //     3 => '<span class="badge badge-danger">Ditolak</span>',
    //   //   ];
    //   //   return $stt[$item->status];
    //   // })
    //   // ->rawColumns(['status'])
    //   ->toJson();

    // $result = json_decode( json_encode($result) );
    // $items = $result->original->data;
        $total = [
            'pendapatan' => 0,
            'b_op' => 0,
            'b_reimburse' => 0,
            'profit' => 0,
            'persentase' => ''
        ];
        foreach ($item AS $i => $unit ) {
          $increment = $i + 1;
          $profit=$unit->invoice_price-$unit->operational_price;
          $percent=round($profit/$unit->invoice_price*100);
          $total['pendapatan'] += $unit->invoice_price;
          $total['b_op'] += $unit->operational_price;
          $total['b_reimburse'] += $unit->talangan_price;
          $total['profit'] += $profit;

          $sheet->SetCellValue($cell1.$urut, $increment);
          $sheet->SetCellValue($cell2.$urut, $unit->customer_name);
          $sheet->SetCellValue($cell3.$urut, $unit->code_wo);
          $sheet->SetCellValue($cell4.$urut, $unit->date_wo);
          $sheet->SetCellValue($cell5.$urut, $unit->service_name);
          $sheet->SetCellValue($cell6.$urut, $unit->qty);
          $sheet->SetCellValue($cell7.$urut, $unit->pieces_name);
          $sheet->SetCellValue($cell8.$urut, $unit->invoice_price);
          $sheet->SetCellValue($cell9.$urut, $unit->operational_price);
          $sheet->SetCellValue($cell10.$urut, $unit->talangan_price);
          $sheet->SetCellValue($cell11.$urut, $profit);
          $sheet->SetCellValue($cell12.$urut, $percent);

          $urut++;
          // dd($opportunity_inquery);
        }

        $total['persentase'] = round($total['profit'] / $total['pendapatan'] * 100);
        $sheet->SetCellValue($cell1.$urut, 'TOTAL');
        $sheet->SetCellValue($cell8.$urut, $total['pendapatan']);
        $sheet->SetCellValue($cell9.$urut, $total['b_op']);
        $sheet->SetCellValue($cell10.$urut, $total['b_reimburse']);
        $sheet->SetCellValue($cell11.$urut, $total['profit']);
        $sheet->SetCellValue($cell12.$urut, $total['persentase']);
        $urut++;

        $sheet->setStyle([
          'font' => [
            'name' => 'Calibri',
            'size' => 11,
          ],
        ]);
        $sheet->setColumnFormat([
          'F' => '#.##0,00',
          'H' => '#,##0.00',
          'I' => '#,##0.00',
          'J' => '#,##0.00',
          'K' => '#,##0.00',
          'L' => '0%'
        ]);
        $sheet->cells('A2:L2', function($cells){
          $cells->setFontWeight('bold');
        });
        $sheet->cells('A3:A1000', function($cells){
          $cells->setFontWeight('bold');
        });
      });
    })->download('xls');
  }

  public function report_html(Request $request)
  {
    $rtype=$request->report_id;
    switch ($rtype) {
      case 1:
        $return=$this->html_1($request);
        break;
      case 2:
        $return=$this->html_2($request);
        break;
      case 3:
        $return=$this->html_3($request);
        break;
      default:
        $return=null;
        break;
    }
    return $return;
  }

  public function html_1($request)
  {
    $wr="1=1";
    if ($request->company_id) {
      $wr.=" AND job_orders.company_id = $request->company_id";
      $company=Company::find($request->company_id);
    }
    if ($request->customer_id) {
      $wr.=" AND job_orders.customer_id = $request->customer_id";
      $customer=Contact::find($request->customer_id);
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and job_orders.shipment_date between '$start' and '$end'";
    }
    $data=[];
    $group=DB::table('service_groups')->orderBy('id','asc')->get();
    foreach ($group as $key => $value) {
      // $dt=DB::table('job_order_costs')
      // ->leftJoin('job_orders','job_orders.id','=','job_order_costs.header_id')
      // ->leftJoin('work_orders','work_orders.id','=','job_orders.work_order_id')
      // ->leftJoin('services','services.id','=','job_orders.service_id')
      // ->whereRaw($wr)
      // ->where('services.service_group_id', $value->id)
      // ->select([
      //   DB::raw("count(DISTINCT job_orders.id) as job_order_total"),
      //   DB::raw("count(DISTINCT work_orders.id) as work_order_total"),
      //   DB::raw("IFNULL(SUM(DISTINCT job_orders.total_unit),0) as qty_total"),
      //   DB::raw("IFNULL(SUM(DISTINCT job_orders.total_price),0) as pendapatan_total"),
      //   DB::raw("IFNULL(SUM(IF(job_order_costs.type=1,job_order_costs.total_price,0)),0) as biaya_total"),
      // ])->first();
      $dt=DB::table('job_orders')
      ->leftJoin('services','services.id','=','job_orders.service_id')
      ->leftJoin(DB::raw('(select sum(total_price) as biaya,header_id from job_order_costs where type = 1 group by header_id) as Y'),'Y.header_id','job_orders.id')
      ->leftJoin(DB::raw('(select sum(qty) as qty_detail,header_id from job_order_details group by header_id) as X'),'X.header_id','job_orders.id')
      ->selectRaw('
      count(job_orders.id) as job_order_total,
      count(distinct job_orders.work_order_id) as work_order_total,
      ifnull(sum(job_orders.total_price),0) as pendapatan_total,
      ifnull(sum(Y.biaya),0) as biaya_total,
      ifnull(sum(if(services.service_type_id in (2,3,4),job_orders.total_unit,X.qty_detail)),0) as qty_total
      ')
      ->whereRaw($wr)
      ->where('services.service_group_id', $value->id)
      ->first();
      $data[]=$dt;
    }
    // dd($data);
    $dts['item']=$data;
    $dts['customer']=$customer??null;
    $dts['company']=$company??null;
    $dts['service_group']=$group;
    return Response::json($dts, 200, [], JSON_NUMERIC_CHECK);
  }

  public function html_2($request)
  {
    $wr="1=1";
    if ($request->company_id) {
      $wr.=" AND company_id = $request->company_id";
      $company=Company::find($request->company_id);
    }
    if ($request->year) {
      $wr.=" AND year(created_at) = '$request->year'";
    }
    $data=[];
    for ($i=1; $i <= 12; $i++) {
      $lead=DB::table('leads')
      ->whereRaw($wr." AND month(created_at) = '$i'")
      ->select([
        DB::raw("count(*) as lead"),
        DB::raw("SUM(IF(step = 6,1,0)) as lead_failed"),
      ])
      ->first();
      $opportunity_inquery=DB::table('inqueries')
      ->whereRaw($wr." AND month(created_at) = '$i'")
      ->select([
        DB::raw("SUM(IF(cancel_opportunity_by is null,1,0)) as opportunity_success"),
        DB::raw("SUM(IF(cancel_opportunity_by is not null,1,0)) as opportunity_failed"),
        DB::raw("SUM(IF(cancel_inquery_by is null,1,0)) as inquery_success"),
        DB::raw("SUM(IF(cancel_inquery_by is not null,1,0)) as inquery_failed"),
      ])
      ->first();
      $quot=DB::table('quotations')
      ->whereRaw($wr." AND month(created_at) = '$i'")
      ->select([
        DB::raw("count(*) as quotation"),
        DB::raw("SUM(IF(status_approve = 5,1,0)) as quotation_failed"),
      ])
      ->first();
      $wo=DB::table('work_orders')
      ->whereRaw($wr." AND month(created_at) = '$i'")
      ->select([
        DB::raw("count(*) as wo"),
      ])
      ->first();
      $data[]=[
        'month' => $this->month[$i],
        'lead' => $lead,
        'opportunity_inquery' => $opportunity_inquery,
        'quotation' => $quot,
        'wo' => $wo,
      ];
      // $sheet->SetCellValue($cell1.$urut,$this->month[$i]);
      // $sheet->SetCellValue($cell2.$urut,$lead->lead??0);
      // $sheet->SetCellValue($cell3.$urut,$lead->lead_failed??0);
      // $sheet->SetCellValue($cell4.$urut,$opportunity_inquery->opportunity_success??0);
      // $sheet->SetCellValue($cell5.$urut,$opportunity_inquery->opportunity_failed??0);
      // $sheet->SetCellValue($cell6.$urut,$opportunity_inquery->inquery_success??0);
      // $sheet->SetCellValue($cell7.$urut,$opportunity_inquery->inquery_failed??0);
      // $sheet->SetCellValue($cell8.$urut,$quot->quotation??0);
      // $sheet->SetCellValue($cell9.$urut,$quot->quotation_failed??0);
      // $sheet->SetCellValue($cell10.$urut,$wo->wo??0);
      // $urut++;
      // dd($opportunity_inquery);
    }
    $dts['item']=$data;
    $dts['company']=$company??null;
    return Response::json($dts, 200, [], JSON_NUMERIC_CHECK);
  }

  public function html_3($request)
  {
    $start_date = $request->start_date;
    $start_date = $start_date != '' ? preg_replace('/([0-9]+)-([0-9]+)-([0-9]+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != '' ? preg_replace('/([0-9]+)-([0-9]+)-([0-9]+)/', '$3-$2-$1', $end_date) : '';
    $service_id = $request->service_group_id;
    $service_id = $service_id != '' ? $service_id : '';

    $wr="1=1";
    if ($request->customer_id) {
      $wr.= " AND jo.customer_id = $request->customer_id";
    }
    if ($request->company_id) {
      $wr.= " AND wo.company_id = $request->company_id";
    }
    if ($request->service_id) {
      $wr.= " AND sg.id = $request->service_id";
    }
    if ($request->start_date && $request->end_date) {
      $start = Carbon::parse($request->start_date)->format('Y-m-d');
      $end = Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" AND wo.date between '$start' and '$end'";
    }

    // if ($request->customer_id) {
    //   $wr.=" AND work_orders.customer_id = $request->customer_id";
    // }
    // if ($request->start_date && $request->end_date) {
    //   $start=Carbon::parse($request->start_date)->format('Y-m-d');
    //   $end=Carbon::parse($request->end_date)->format('Y-m-d');
    //   $wr.=" and date(work_orders.created_at) between '$start' and '$end'";
    // }
    // $sql="
    // SELECT
    // 	work_orders.code as code_wo,
    // 	work_orders.date as date_wo,
    // 	sum( invoice_details.total_price ) AS invoice_price,
    // 	ifnull( sum( Y.biaya ), 0 ) AS operational_price,
    // 	ifnull( sum( Y.reimburse ), 0 ) AS talangan_price,
    // 	services.name as service_name,
    // 	invoice_details.qty,
    // 	invoice_details.imposition_name as pieces_name,
    // 	sum(invoice_details.total_price)-ifnull( sum( Y.biaya ), 0 ) as profit,
    // 	round((sum(invoice_details.total_price)-ifnull( sum( Y.biaya ), 0 ))/sum(invoice_details.total_price)*100,2) as presentase
    // FROM
    // 	invoice_details
    // 	LEFT JOIN job_orders ON job_orders.id = invoice_details.job_order_id
    // 	LEFT JOIN work_orders ON work_orders.id = job_orders.work_order_id
    // 	LEFT JOIN services ON services.id = job_orders.service_id
    // 	LEFT JOIN service_groups ON service_groups.id = services.service_group_id
    // 	LEFT JOIN (
    // SELECT
    // 	sum( IF ( type = 1, total_price, 0 ) ) AS biaya,
    // 	sum( IF ( type = 2, total_price, 0 ) ) AS reimburse,
    // 	header_id
    // FROM
    // 	job_order_costs
    // GROUP BY
    // 	header_id
    // 	) Y ON Y.header_id = job_orders.id
    // WHERE
    // 	invoice_details.job_order_id IS NOT NULL and $wr
    // GROUP BY
    // 	services.NAME,
    // 	work_orders.id
    // order by date desc
    // ";

    $sql="
    select
    date(work_orders.date) as date_wo,
    contacts.name as customer_name,
    sum(ifnull((select sum(total_price) from job_order_costs where header_id = jo.id and type = 1 and status in (3,5,8)),0)) as operational_price,
    sum(ifnull((select sum(total_price) from job_order_costs where header_id = jo.id and type = 2 and status in (3,5,8)),0)) as talangan_price,
    sum(total_price) as invoice_price,
    work_orders.code as code_wo,
    service_groups.name as service_name,
    max(X.satuan) as pieces_name,
    if(X.stype in (6),1,if(X.stype in (2,3,4),sum(X.qty))) as qty,
    contacts.name as customer
    from job_orders as jo
    left join contacts on contacts.id = jo.customer_id
    left join work_orders on work_orders.id = jo.work_order_id
    left join work_order_details on work_order_details.id = jo.work_order_detail_id
    left join services on services.id = jo.service_id
    left join service_groups on service_groups.id = services.service_group_id
    left join service_types on service_types.id = jo.service_type_id
    left join (select job_orders.id, job_orders.service_type_id as stype, if(job_orders.service_type_id=2,'Kontainer',if(job_orders.service_type_id=3,'Unit',if(job_orders.service_type_id in (6,7),pieces.name,if(quotation_details.imposition=1,'Kubikasi',if(quotation_details.imposition=2,'Tonase','Item'))))) as satuan, IF(job_orders.service_type_id in (2,3,4),max(job_orders.total_unit),sum(jod.qty)) as qty from job_orders left join (select sum(qty) as qty, header_id from job_order_details group by header_id) jod on jod.header_id = job_orders.id left join quotation_details on quotation_details.id = job_orders.quotation_detail_id left join pieces on quotation_details.piece_id = pieces.id group by job_orders.id) X on X.id = jo.id
    where $wr
    group by services.service_group_id, jo.work_order_id
    order by work_orders.date desc, work_orders.id asc
    ";
    // $item = DB::select($sql);
DB::enableQueryLog();
    // $wr.=" and sg.id is not null";
    $item = DB::table('work_orders as wo')
    ->leftJoin('work_order_details as wod', 'wo.id', 'wod.header_id')
    ->leftJoin('price_lists as pl', 'pl.id', 'wod.price_list_id')
    ->leftJoin('services as spl', 'spl.id', 'pl.service_id')
    ->leftJoin('service_groups as sgpl', 'sgpl.id', 'spl.service_group_id')
    ->leftJoin('pieces as ppl', 'ppl.id', 'pl.piece_id')
    ->leftJoin('quotations as qt', 'wo.quotation_id', 'qt.id')
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
      wo.date as date_wo,
      wo.code as code_wo,
      sum(if(joc.type=1 and joc.status in(3,5,8),joc.total_price,0)) as operational_price,
      sum(if(joc.type=2 and joc.status in(3,5,8),joc.total_price,0)) as talangan_price,
      if(qt.bill_type=2,(wo.qty*qt.price_full_contract),sum(distinct jo.total_price)) as invoice_price,
      if(qt.bill_type=2,wo.qty,if(max(jo.service_type_id) in (2,3,4),max(jo.total_unit),if(max(jo.service_type_id) in (6),count(distinct jo.id),jod.qty))) as qty,
      c.name AS customer_name,
      c.name AS customer,
      sg.name AS service_name,
      sgpl.name as service_name_pl,
      sg.id as service_group_id,
      if(wo.quotation_id is not null,1,2) as type,
      if(qd.service_type_id in (6,7),p.name,if(qd.service_type_id=2,'Kontainer',if(qd.service_type_id=3,'Unit',imp.name))) as pieces_name,
      if(pl.service_type_id in (6,7),ppl.name,if(pl.service_type_id=2,'Kontainer',if(pl.service_type_id=3,'Unit','Kubikasi/Tonase/item'))) as pieces_name_pl
    ")
    ->groupBy('wo.id','sg.id','sgpl.id')
    ->havingRaw('invoice_price > 0')
    ->orderBy('wo.date','desc')
    ->orderBy('wo.created_at','desc')
    ->get();

    // dd(DB::getQueryLog());
    $result = DataTables::of($item)
      ->editColumn('date_wo', function($item){
        return dateView($item->date_wo);
      })
      // ->editColumn('operational_price', function($item){
      //   return formatNumber($item->operational_price);
      // })
      // ->editColumn('talangan_price', function($item){
      //   return formatNumber($item->talangan_price);
      // })
      // ->editColumn('invoice_price', function($item){
      //   return formatNumber($item->invoice_price);
      // })
      // ->editColumn('profit', function($item){
      //   return formatNumber($item->profit);
      // })
      // ->editColumn('presentase', function($item){
      //   return formatNumber($item->presentase).' %';
      // })
      // ->editColumn('status', function($item){
      //   $stt=[
      //     1 => '<span class="badge badge-warning">Diajukan</span>',
      //     2 => '<span class="badge badge-success">Disetujui</span>',
      //     3 => '<span class="badge badge-danger">Ditolak</span>',
      //   ];
      //   return $stt[$item->status];
      // })
      // ->rawColumns(['status'])
      ->toJson();

    $result = json_decode( json_encode($result) );
    $result = $result->original->data;
    $dts['item'] = $result;
    return Response::json($dts, 200, [], JSON_NUMERIC_CHECK);
  }
}
