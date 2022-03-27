<?php

namespace App\Http\Controllers\Export;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Excel;
use App\Model\WorkOrder;
use App\Model\Invoice;
use App\Model\KpiLog;
use App\Model\Lead;
use App\Model\Quotation;
use App\Model\Inquery;
use App\Model\PriceList;
use App\Model\customerprice;
use App\Model\QuotationDetail;
use App\Model\UsingItem;
use App\Model\VehicleChecklistItem;
use App\Http\Controllers\Api\FinanceApiController;
use App\Http\Controllers\Api\OperationalApiController;
use DataTables;

class ExportExcelController extends Controller
{
  // EXPORT SETTING

  public function stock_transaction_export(Request $request)
  {
    $wr="WHERE 1=1";
    if (isset($request->item_id)) {
      $wr .= " AND stock_transactions.item_id = ".$request->item_id;
    } else {
      $wr .= " AND stock_transactions.item_id = 0";
    }
    if (isset($request->warehouse_id)) {
      $wr .= " AND stock_transactions.warehouse_id = ".$request->warehouse_id;
    } else {
      $wr .= " AND stock_transactions.warehouse_id = 0";
    }

    DB::statement(DB::raw("set @balance = 0"));
    $sql = "select type_transactions.name as type_trx_name, date_transaction, code, description, qty_masuk, qty_keluar, (@balance := @balance + (qty_masuk - qty_keluar)) as total from stock_transactions left join type_transactions on type_transactions.id = stock_transactions.type_transaction_id ".$wr." ORDER BY stock_transactions.date_transaction asc;";
    $items = DB::select(DB::raw($sql));
    $item = collect($items);

    // $item = WarehouseStock::with('warehouse.company','warehouse','item','item.category')->select('warehouse_stocks.*');

    $result = DataTables::of($item)
      ->editColumn('total', function($item){
        return formatNumber($item->total);
      })
      ->editColumn('qty_masuk', function($item){
        return formatNumber($item->qty_masuk);
      })
      ->editColumn('qty_keluar', function($item){
        return formatNumber($item->qty_keluar);
      })
      ->skipPaging()->make(true);

      $result_encoded = json_decode( json_encode($result) );
      $data = $result_encoded->original->data;

    return Excel::create('Kartu Persediaan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Tanggal Transaksi');
        $sheet->SetCellValue('C1','Kode Transaksi');
        $sheet->SetCellValue('D1','Tipe');
        $sheet->SetCellValue('E1','Qty Masuk');
        $sheet->SetCellValue('F1','Qty Keluar');
        $sheet->SetCellValue('G1','Saldo Akhir');
        $sheet->SetCellValue('H1','Keterangan');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $urut=$i+2;

          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->date_transaction);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->type_trx_name);
          $sheet->SetCellValue('E'.$urut,$value->qty_masuk);
          $sheet->SetCellValue('F'.$urut,$value->qty_keluar);
          $sheet->SetCellValue('G'.$urut,$value->total);
          $sheet->SetCellValue('H'.$urut,$value->description);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function area_export()
  {
    $data=DB::table('areas')
    ->select(
      DB::raw("areas.name as area"),
      DB::raw("areas.created_at as tanggal_pembuatan")
      )
    ->orderBy('id','asc')
    ->get();

    return Excel::create('Semua Area',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama Area');
        $sheet->SetCellValue('C1','Waktu Pembuatan Area');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $urut=$i+2;

          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->area);
          $sheet->SetCellValue('C'.$urut,$value->tanggal_pembuatan);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function company_export()
  {
    $data=DB::table('companies')
    ->leftJoin('areas','areas.id','=', 'companies.area_id')
    ->leftJoin('cities','cities.id','=', 'companies.city_id')
    ->leftJoin('accounts as akun_kas','akun_kas.id','=', 'companies.cash_account_id')
    ->leftJoin('accounts as akun_bank','akun_bank.id','=', 'companies.bank_account_id')
    ->leftJoin('accounts as akun_mutasi','akun_mutasi.id','=', 'companies.mutation_account_id')
    ->select(
      'areas.name as area',
      'cities.name as city',
      'companies.code',
      'companies.name as company',
      'companies.address',
      'companies.phone',
      'companies.email',
      'companies.website',
      'companies.is_pusat',
      'companies.rek_no_1',
      'companies.rek_name_1',
      'companies.rek_bank_1',
      'companies.rek_no_2',
      'companies.rek_name_2',
      'companies.rek_bank_2',
      'companies.plafond',
      'akun_kas.name as akun_kas',
      'akun_bank.name as akun_bank',
      'akun_mutasi.name as akun_mutasi'
      )
    ->orderBy('companies.id','asc')
    ->get();

    return Excel::create('Semua Cabang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama Area');
        $sheet->SetCellValue('C1','Nama Kota');
        $sheet->SetCellValue('D1','Kode');
        $sheet->SetCellValue('E1','Nama Cabang');
        $sheet->SetCellValue('F1','Alamat');
        $sheet->SetCellValue('G1','Telepon');
        $sheet->SetCellValue('H1','Email');
        $sheet->SetCellValue('I1','Website');
        $sheet->SetCellValue('J1','Pusat');
        $sheet->SetCellValue('K1','Nomor Rekening 1');
        $sheet->SetCellValue('L1','Nama Rekening 1');
        $sheet->SetCellValue('M1','Bank Rekening 1');
        $sheet->SetCellValue('N1','Nomor Rekening 2');
        $sheet->SetCellValue('O1','Nama Rekening 2');
        $sheet->SetCellValue('P1','Bank Rekening 2');
        $sheet->SetCellValue('Q1','Plafond');
        $sheet->SetCellValue('R1','Akun Kas');
        $sheet->SetCellValue('S1','Akun Bank');
        $sheet->SetCellValue('T1','Akun Mutasi Kas/Bank');

        $nomer = 1;
        $pusat = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          if($value->is_pusat == 1){
            $pusat = "Ya";
          }else{
            $pusat = "Tidak";
          }

          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->area);
          $sheet->SetCellValue('C'.$urut,$value->city);
          $sheet->SetCellValue('D'.$urut,$value->code);
          $sheet->SetCellValue('E'.$urut,$value->company);
          $sheet->SetCellValue('F'.$urut,$value->address);
          $sheet->SetCellValue('G'.$urut,$value->phone);
          $sheet->SetCellValue('H'.$urut,$value->email);
          $sheet->SetCellValue('I'.$urut,$value->website);
          $sheet->SetCellValue('J'.$urut,$pusat);
          $sheet->SetCellValue('K'.$urut,$value->rek_no_1);
          $sheet->SetCellValue('L'.$urut,$value->rek_name_1);
          $sheet->SetCellValue('M'.$urut,$value->rek_bank_1);
          $sheet->SetCellValue('N'.$urut,$value->rek_no_2);
          $sheet->SetCellValue('O'.$urut,$value->rek_name_2);
          $sheet->SetCellValue('P'.$urut,$value->rek_bank_2);
          $sheet->SetCellValue('Q'.$urut,$value->plafond);
          $sheet->SetCellValue('R'.$urut,$value->akun_kas);
          $sheet->SetCellValue('S'.$urut,$value->akun_bank);
          $sheet->SetCellValue('T'.$urut,$value->akun_mutasi);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function contract_price_export(Request $request)
  {
    $wr="1=1 and quotations.is_contract = 1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND quotations.company_id = ".auth()->user()->company_id;
    }

    if (isset($request->customer_id) && !empty($request->customer_id)) {
      $wr.=" AND quotations.customer_id = {$request->customer_id}";
    }

    if (isset($request->start_date) && !empty($request->start_date)) {
        $wr.=" AND quotations.date_contract >= {$request->start_date}";
    }

    if (isset($request->end_date) && !empty($request->end_date)) {
        $wr.=" AND quotations.date_contract <= {$request->end_date}";
    }

    $item=DB::table('quotation_details')
    ->leftJoin('services','services.id','quotation_details.service_id')
    ->leftJoin('routes','routes.id','quotation_details.route_id')
    ->leftJoin('commodities','commodities.id','quotation_details.commodity_id')
    ->leftJoin('vehicle_types','vehicle_types.id','quotation_details.vehicle_type_id')
    ->leftJoin('container_types','container_types.id','quotation_details.container_type_id')
    ->leftJoin('quotations','quotations.id','quotation_details.header_id')
    ->leftJoin('companies','companies.id','quotations.company_id')
    ->leftJoin('contacts','contacts.id','quotations.customer_id')
    ->leftJoin('pieces','pieces.id','quotation_details.piece_id')
    ->leftJoin('impositions','impositions.id','quotation_details.imposition')
    ->whereRaw($wr)
    ->selectRaw("
    quotation_details.*,
    routes.name as trayek,
    services.name as service,
    commodities.name as commodity,
    if(vehicle_types.id is not null,vehicle_types.name,container_types.code) as vehicle_type,
    quotations.no_contract,
    contacts.name as customer,
    companies.name as company,
    if(quotation_details.service_type_id in (6,7),pieces.name,if(quotation_details.service_type_id=2,'Kontainer',if(quotation_details.service_type_id=3,'Unit',impositions.name))) as imposition_name
    ");

    $datatable = DataTables::of($item)
      ->addColumn('action', function($item){
        // $html="<a><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
        $html="<a ng-show=\"roleList.includes('marketing.price.contract_price.detail')\" ui-sref=\"marketing.contract_price.show({id:$item->id})\"><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->filterColumn('imposition_name', function($query, $keyword) {
        $sql="if(quotation_details.service_type_id in (6,7),pieces.name,if(quotation_details.service_type_id=2,'Kontainer',if(quotation_details.service_type_id=3,'Unit',impositions.name))) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->filterColumn('vehicle_type', function($query, $keyword) {
        $sql="if(vehicle_types.id is not null,vehicle_types.name,container_types.code) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->editColumn('price_contract_full', function($item){
        return number_format($item->price_contract_full);
      })
      ->editColumn('imposition', function($item){
        $ret="";
        $stt=[
          1 => 'Kubikasi',
          2 => 'Tonase',
          3 => 'Item',
        ];
        if (isset($item->imposition)) {
          $ret.=$stt[$item->imposition];
        }
        return $ret;
      })
      ->editColumn('is_generate', function($item){
        $stt=[
          1 => 'Aktif',
          0 => 'Tidak Aktif',
        ];
        return $stt[$item->is_generate];
      })
      ->rawColumns(['action'])
      ->skipPaging()->make(true);
      $datatable = json_decode( json_encode($datatable));
      
      $data = $datatable->original->data;

      return Excel::create('Tarif Kontrak',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','Cabang');
        $sheet->SetCellValue('B1','Layanan');
        $sheet->SetCellValue('C1','Trayek');
        $sheet->SetCellValue('D1','Customer');
        $sheet->SetCellValue('E1','Komoditas');
        $sheet->SetCellValue('F1','Tipe Kendaraan');
        $sheet->SetCellValue('G1','Pengenaan');
        $sheet->SetCellValue('H1','Harga');
        $sheet->SetCellValue('I1','Status');

        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
         
          $sheet->SetCellValue('A'.$urut,$value->company);
          $sheet->SetCellValue('B'.$urut,$value->service);
          $sheet->SetCellValue('C'.$urut,$value->trayek);
          $sheet->SetCellValue('D'.$urut,$value->customer);
          $sheet->SetCellValue('E'.$urut,$value->commodity);
          $sheet->SetCellValue('F'.$urut,$value->vehicle_type);
          $sheet->SetCellValue('G'.$urut,$value->imposition_name);
          $sheet->SetCellValue('H'.$urut,$value->price_contract_full);
          $sheet->SetCellValue('I'.$urut,$value->is_generate);
        }
      });
    })->download('xls');
  }

  public function price_list_export(Request $request)
  {

    $wr="1=1";
    if ($request->disable4=='true') {
      $wr.=" AND price_lists.service_type_id != 4";
    }
    if (auth()->user()->is_admin==0) {
      $wr.=" AND price_lists.company_id = ".auth()->user()->company_id;
    }
    $item = PriceList::with('company','commodity','service','piece','route','moda','vehicle_type','container_type','service_type')->whereRaw($wr)->select('price_lists.*');
    if (isset($request->service_id)) {
      $wr.=" AND price_lists.service_id = $request->service_id";
    }

    if (isset($request->company_id)) {
      $wr.=" AND price_lists.company_id = $request->company_id";
    }

    if (isset($request->service_type_id))
      $wr.=" AND price_lists.service_type_id = $request->service_type_id";

    if (isset($request->company_id))
      $wr.=" AND price_lists.company_id = $request->company_id";

    $item = PriceList::with('company','commodity','service','piece','route','moda','vehicle_type','container_type','service_type')
        ->whereRaw($wr)
        ->select('price_lists.*');
    
    $datatable = DataTables::of($item)
      ->make(true);

      $datatable = json_decode( json_encode($datatable));
      $data = $datatable->original->data;

      return Excel::create('Tarif Umum',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','Cabang');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Trayek');
        $sheet->SetCellValue('D1','Nama Tarif');
        $sheet->SetCellValue('E1','Komoditas');
        $sheet->SetCellValue('F1','Satuan');
        $sheet->SetCellValue('G1','Layanan');
        $sheet->SetCellValue('H1','Tipe');
        $sheet->SetCellValue('I1','Moda');
        $sheet->SetCellValue('J1','Kendaraan');

        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
         
          $sheet->SetCellValue('A'.$urut,@$value->company->name);
          $sheet->SetCellValue('B'.$urut,@$value->code);
          $sheet->SetCellValue('C'.$urut,@$value->route->name);
          $sheet->SetCellValue('D'.$urut,@$value->name);
          $sheet->SetCellValue('E'.$urut,@$value->commodity->name);
          $sheet->SetCellValue('F'.$urut,@$value->piece->name);
          $sheet->SetCellValue('G'.$urut,@$value->price_name);
          $sheet->SetCellValue('H'.$urut,@$value->price_type_name);
          $sheet->SetCellValue('I'.$urut,@$value->moda->name);
          $sheet->SetCellValue('J'.$urut,@$value->vehicle_type->name);
        }
      });
    })->download('xls');
  }



  public function price_list_vendor(Request $request)
  {

    $wr="1=1";
    if ($request->disable4=='true') {
      $wr.=" AND price_lists.service_type_id != 4";
    }
    if (auth()->user()->is_admin==0) {
      $wr.=" AND price_lists.company_id = ".auth()->user()->company_id;
    }
    $item = PriceList::with('company','commodity','service','piece','route','moda','vehicle_type','container_type','service_type')->whereRaw($wr)->select('price_lists.*');

    $datatable = DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ui-sref=\"marketing.vendor_price.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ui-sref=\"vendor.register_vendor.show.price.edit({id:$item->vendor_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->addColumn('action_marketing', function($item){
        $html="<a ui-sref=\"marketing.vendor_price.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ui-sref=\"marketing.vendor_price.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->addColumn('action_fr_contact', function($item){
        $html="<a ui-sref=\"contact.vendor.show.price.edit({id:$item->vendor_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->addColumn('action_approve', function($item){
        $html="<a ui-sref=\"vendor.vendor.show.price.edit({id:$item->vendor_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->rawColumns(['action','action_approve','action_fr_contact','action_marketing'])
      ->make(true);

      $datatable = json_decode( json_encode($datatable));
      $data = $datatable->original->data;

      return Excel::create('Harga Vendor',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','Cabang');
        $sheet->SetCellValue('B1','Vendor');
        $sheet->SetCellValue('C1','Trayek');
        $sheet->SetCellValue('D1','Nama Tarif');
        $sheet->SetCellValue('E1','Komoditas');
        $sheet->SetCellValue('F1','Satuan');
        $sheet->SetCellValue('G1','Layanan');
        $sheet->SetCellValue('H1','Tipe');
        $sheet->SetCellValue('I1','Moda');
        $sheet->SetCellValue('J1','Kendaraan');

        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
         
          $sheet->SetCellValue('A'.$urut,@$value->company->name);
          $sheet->SetCellValue('B'.$urut,@$value->vendor->name);
          $sheet->SetCellValue('C'.$urut,@$value->route->name);
          $sheet->SetCellValue('D'.$urut,@$value->name);
          $sheet->SetCellValue('E'.$urut,@$value->commodity->name);
          $sheet->SetCellValue('F'.$urut,@$value->piece->name);
          $sheet->SetCellValue('G'.$urut,@$value->service->name);
          $sheet->SetCellValue('H'.$urut,@$value->service_type->name);
          $sheet->SetCellValue('I'.$urut,@$value->moda->name);
          $sheet->SetCellValue('J'.$urut,@$value->vehicle_type->name);
        }
      });
    })->download('xls');
  }

  public function customer_list_export(Request $request)
  {

    $wr="1=1";
    if ($request->disable4=='true') {
      $wr.=" AND price_lists.service_type_id != 4";
    }
    if (auth()->user()->is_admin==0) {
      $wr.=" AND price_lists.company_id = ".auth()->user()->company_id;
    }
    $item = CustomerPrice::with('customer','company','commodity','service','piece','route','moda','vehicle_type','service_type')->whereRaw($wr)->selectRaw('customer_prices.*')->orderBy('created_at', 'DESC')->get();

    $datatable = DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('')\" ui-sref=\"vendor.register_vendor.show.price.edit({id:$item->customer_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->addColumn('action_marketing', function($item){
        $html="<a ui-sref=\"marketing.customer_price.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ui-sref=\"marketing.customer_price.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->addColumn('action_fr_contact', function($item){
        $html="<a ng-show=\"roleList.includes('')\" ui-sref=\"contact.vendor.show.price.edit({id:$item->customer_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->addColumn('action_approve', function($item){
        $html="<a ng-show=\"roleList.includes('')\" ui-sref=\"vendor.vendor.show.price.edit({id:$item->vendor_id,idprice:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        return $html;
      })
      ->rawColumns(['action','action_approve','action_fr_contact','action_marketing'])
      ->make(true);

      $datatable = json_decode( json_encode($datatable));
      $data = $datatable->original->data;

      return Excel::create('Harga Customer',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','Cabang');
        $sheet->SetCellValue('B1','Customer');
        // $sheet->SetCellValue('C1','Trayek');
        $sheet->SetCellValue('C1','Nama Tarif');
        $sheet->SetCellValue('D1','Komoditas');
        $sheet->SetCellValue('E1','Satuan');
        $sheet->SetCellValue('F1','Layanan');
        $sheet->SetCellValue('G1','Tipe');
        $sheet->SetCellValue('H1','Moda');
        $sheet->SetCellValue('I1','Kendaraan');

        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
         
          $sheet->SetCellValue('A'.$urut,@$value->company->name);
          $sheet->SetCellValue('B'.$urut,@$value->customer->name);
          // $sheet->SetCellValue('C'.$urut,@$value->route->name);
          $sheet->SetCellValue('C'.$urut,@$value->name);
          $sheet->SetCellValue('D'.$urut,@$value->commodity->name);
          $sheet->SetCellValue('E'.$urut,@$value->piece->name);
          $sheet->SetCellValue('F'.$urut,@$value->price_name);
          $sheet->SetCellValue('G'.$urut,@$value->price_type_name);
          $sheet->SetCellValue('H'.$urut,@$value->moda->name);
          $sheet->SetCellValue('I'.$urut,@$value->vehicle_type->name);
        }
      });
    })->download('xls');
  }

  public function lead_export(Request $request)
  {

    $wr="1=1";
    if ($request->company_id) {
      $wr.=" AND leads.company_id = $request->company_id";
    }
    if ($request->step) {
      $wr.=" AND leads.step = $request->step";
    }
    if ($request->lead_status_id) {
      $wr.=" AND leads.lead_status_id = $request->lead_status_id";
    }
    if ($request->lead_source_id) {
      $wr.=" AND leads.lead_source_id = $request->lead_source_id";
    }
    if ($request->name) {
      $wr.=" AND leads.name LIKE '%$request->name%'";
    }
    $item = Lead::with('company','lead_source','lead_status')->whereRaw($wr)->select('leads.*',DB::raw("CONCAT(IFNULL(leads.phone,'-'),', ',IFNULL(leads.phone2,'-')) as phone_lengkap"));

    $datatable = DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('marketing.leads.detail')\"><span class='fa fa-folder-o' ui-sref='marketing.lead.show({id:$item->id})'></span></a>&nbsp;&nbsp;";
        if ($item->step==1) {
          $html.="<a ng-show=\"roleList.includes('marketing.leads.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        }
        return $html;
      })
      ->filterColumn('phone_lengkap', function($query, $keyword) {
        $sql = "CONCAT(IFNULL(leads.phone,'-'),', ',IFNULL(leads.phone2,'-')) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->editColumn('step', function($item){
        $stt=[
          1 => 'Lead',
          2 => 'Opportunity',
          3 => 'Inquery',
          4 => 'Quotation',
          5 => 'Kontrak',
          6 => 'Batal Lead',
          7 => 'Batal Opportunity',
          8 => 'Batal Inquery',
          9 => 'Batal Quotation',
        ];
        return $stt[$item->step];
      })
      ->editColumn('created_at', function($item){
        return dateView($item->created_at);
      })
      ->rawColumns(['action'])
      ->skipPaging()->make(true);

      $datatable = json_decode( json_encode($datatable));
      $data = $datatable->original->data;

      return Excel::create('Lead',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','Cabang');
        $sheet->SetCellValue('B1','Tanggal Lead');
        $sheet->SetCellValue('C1','Nama Lead');
        $sheet->SetCellValue('D1','Alamat');
        $sheet->SetCellValue('E1','Telephone');
        $sheet->SetCellValue('F1','Email');
        $sheet->SetCellValue('G1','Source');
        $sheet->SetCellValue('H1','Status');
        $sheet->SetCellValue('I1','Tahapan');

        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
         
          $sheet->SetCellValue('A'.$urut,@$value->company->name);
          $sheet->SetCellValue('B'.$urut,@$value->created_at);
          $sheet->SetCellValue('C'.$urut,@$value->name);
          $sheet->SetCellValue('D'.$urut,@$value->address);
          $sheet->SetCellValue('E'.$urut,@$value->phone_lengkap);
          $sheet->SetCellValue('F'.$urut,@$value->email);
          $sheet->SetCellValue('G'.$urut,@$value->lead_source->name);
          $sheet->SetCellValue('H'.$urut,@$value->lead_status->status);
          $sheet->SetCellValue('I'.$urut,@$value->step);
        }
      });
    })->download('xls');
  }

  public function opportunity_export(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND inqueries.company_id = ".auth()->user()->company_id;
    }

    $item = Inquery::with('customer','customer_stage','sales_opportunity')->whereRaw($wr)->whereIn('status', [1,5]);

    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status', $status) : $item;
    $customer_stage_id = $request->customer_stage_id;
    $customer_stage_id = $customer_stage_id != null ? $customer_stage_id : '';
    $item = $customer_stage_id != '' ? $item->where('customer_stage_id', $customer_stage_id) : $item;
    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $item = $start_date != '' && $end_date != ' ' ? $item->whereBetween('date_opportunity', [$start_date, $end_date]) : $item;

    $item = $item->select('inqueries.*');

    $datatable = DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('marketing.opportunity.detail')\" ui-sref=\"marketing.opportunity.show({id:$item->id})\"><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
        if (in_array($item->status,[1])) {
          $html.="<a ng-show=\"roleList.includes('marketing.opportunity.edit')\" ui-sref=\"marketing.opportunity.edit({id:$item->id})\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('marketing.opportunity.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        }
        return $html;
      })
      ->editColumn('date_opportunity', function($item){
        return dateView($item->date_opportunity);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => 'Opportunity',
          2 => 'Inquery',
          3 => 'Quotation',
          4 => 'Contract',
          5 => 'Batal Opportunity',
          6 => 'Batal Inquery',
          7 => 'Batal Quotation',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action'])
      ->skipPaging()->make(true);

      $datatable = json_decode( json_encode($datatable));
      $data = $datatable->original->data;

      return Excel::create('Opportunity',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','Kode Opportunity');
        $sheet->SetCellValue('B1','Tanggal');
        $sheet->SetCellValue('C1','Customer');
        $sheet->SetCellValue('D1','Stage');
        $sheet->SetCellValue('E1','Sales');
        $sheet->SetCellValue('F1','Catatan');
        $sheet->SetCellValue('G1','Status');

        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,@$value->code_opportunity);
          $sheet->SetCellValue('B'.$urut,@$value->date_opportunity);
          $sheet->SetCellValue('C'.$urut,@$value->customer->name);
          $sheet->SetCellValue('D'.$urut,@$value->customer_stage->name);
          $sheet->SetCellValue('E'.$urut,@$value->sales_opportunity->name);
          $sheet->SetCellValue('F'.$urut,@$value->description_opportunity);
          $sheet->SetCellValue('G'.$urut,@$value->status);
        }
      });
    })->download('xls');
  }

  public function quotation_export(Request $request)
  {
    $wr="1=1";
    if (isset($request->is_contact)) {
      $wr.=" and quotations.is_contract = $request->is_contact";
    }
    if ($request->status_approve) {
      $wr.=" and quotations.status_approve = $request->status_approve";
    }
    if (isset($request->customer_id)) {
      $wr.=" and quotations.customer_id = $request->customer_id";
    } else {
      if (auth()->user()->is_admin==0) {
        $wr.=" and quotations.company_id = ".auth()->user()->company_id;
      }
    }
    if (isset($request->is_active)) {
      $wr.=" and quotations.is_active = $request->is_active";
    }
    if (isset($request->is_parent_null)) {
      $wr.=" and quotations.parent_id is null";
    }
    if (isset($request->end_date_more)) {
      $wr.=" and quotations.date_end_contract >= date(now())";
    }
    $item = Quotation::with('customer','sales','customer_stage')->whereRaw($wr);
    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status_approve', $status) : $item;
    $customer_stage_id = $request->customer_stage_id;
    $customer_stage_id = $customer_stage_id != null ? $customer_stage_id : '';
    $item = $customer_stage_id != '' ? $item->where('customer_stage_id', $customer_stage_id) : $item;
    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $item = $start_date != '' && $end_date != ' ' ? $item->whereBetween('date_inquery', [$start_date, $end_date]) : $item;

    $item = $item->select('quotations.*',DB::raw("IF(type_entry=1,'WEBSITE',IF(type_entry=2,'OPERATOR','ANDROID')) as type_entryy"));

    $datatable = DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('marketing.quotation.detail')\" ui-sref=\"marketing.inquery.show({id:$item->id})\"><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
        if ($item->status_approve==1) {
          $html.="<a ng-show=\"roleList.includes('marketing.quotation.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        }
        return $html;
      })
      ->addColumn('action_customer', function($item){
        $html="<a ui-sref=\"main.quotation.show({id:$item->id})\"><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html='<a ng-click=\'chooseKontrak('.json_encode($item,JSON_HEX_APOS).')\' class="btn btn-xs btn-success">Pilih</a>';
        return $html;
      })
      ->editColumn('date_end_contract', function($item){
        return dateView($item->date_end_contract);
      })
      ->editColumn('status_approve', function($item){
        $stt=[
          1 => 'Penawaran',
          2 => 'Penawaran Diajukan',
          3 => 'Penawaran Disetujui',
          4 => 'Kontrak',
          5 => 'Penawaran Ditolak',
          6 => 'Batal Quotation',
        ];
        return $stt[$item->status_approve];
      })
      ->editColumn('bill_type', function($item){
        $stt=[
          1 => 'Per Pengiriman',
          2 => 'Borongan',
        ];
        return $stt[$item->bill_type];
      })
      ->filterColumn('type_entryy', function($query, $keyword) {
          $sql = "IF(type_entry=1,'WEBSITE',IF(type_entry=2,'OPERATOR','ANDROID')) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
          })
      ->rawColumns(['action','action_choose','action_customer'])
      ->skipPaging()->make(true);

      $datatable = json_decode( json_encode($datatable));
      $data = $datatable->original->data;

      return Excel::create('Quotation',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
      $sheet->SetCellValue('A1','Nama');
      $sheet->SetCellValue('B1','Kode');
      $sheet->SetCellValue('C1','Tanggal');
      $sheet->SetCellValue('D1','Customer');
      $sheet->SetCellValue('E1','Stage');
      $sheet->SetCellValue('F1','Sales');
      $sheet->SetCellValue('G1','Kontrak');
      $sheet->SetCellValue('H1','Entri Via');
      $sheet->SetCellValue('I1','Status');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,@$value->name);
          $sheet->SetCellValue('B'.$urut,@$value->code);
          $sheet->SetCellValue('C'.$urut,@$value->date_inquery);
          $sheet->SetCellValue('D'.$urut,@$value->customer->name);
          $sheet->SetCellValue('E'.$urut,@$value->customer_stage->name);
          $sheet->SetCellValue('F'.$urut,@$value->sales->name);
          $sheet->SetCellValue('G'.$urut,@$value->no_contract);
          $sheet->SetCellValue('H'.$urut,@$value->type_entryy);
          $sheet->SetCellValue('I'.$urut,@$value->status_approve);
        }
      });
    })->download('xls');
  }

  public function inquery_export(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND inqueries.company_id = ".auth()->user()->company_id;
    }
    $item = Inquery::with('customer','customer_stage','sales_inquery')->whereRaw($wr)->whereIn('status', [2,6]);


    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status', $status) : $item;
    $customer_stage_id = $request->customer_stage_id;
    $customer_stage_id = $customer_stage_id != null ? $customer_stage_id : '';
    $item = $customer_stage_id != '' ? $item->where('customer_stage_id', $customer_stage_id) : $item;
    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $item = $start_date != '' && $end_date != ' ' ? $item->whereBetween('date_inquery', [$start_date, $end_date]) : $item;


    $item = $item->select('inqueries.*');

    $datatable = DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('marketing.Inquery.detail')\" ui-sref=\"marketing.inquery_qt.show({id:$item->id})\"><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
        if (in_array($item->status,[1,2])) {
          $html.="<a ng-show=\"roleList.includes('marketing.Inquery.edit')\" ui-sref=\"marketing.inquery_qt.edit({id:$item->id})\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('marketing.Inquery.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
        }
        return $html;
      })
      ->editColumn('date_inquery', function($item){
        return dateView($item->date_inquery);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => 'Opportunity',
          2 => 'Inquery',
          3 => 'Quotation',
          4 => 'Contract',
          5 => 'Batal Opportunity',
          6 => 'Batal Inquery',
          7 => 'Batal Quotation',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action'])
      ->skipPaging()->make(true);

      $datatable = json_decode( json_encode($datatable));
      $data = $datatable->original->data;

      return Excel::create('Inquery',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
      $sheet->SetCellValue('A1','Kode Opportunity');
      $sheet->SetCellValue('B1','Tanggal');
      $sheet->SetCellValue('C1','Customer');
      $sheet->SetCellValue('D1','Stage');
      $sheet->SetCellValue('E1','Sales');
      $sheet->SetCellValue('F1','Catatan');
      $sheet->SetCellValue('G1','Status');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,@$value->code_inquery);
          $sheet->SetCellValue('B'.$urut,@$value->date_inquery);
          $sheet->SetCellValue('C'.$urut,@$value->customer->name);
          $sheet->SetCellValue('D'.$urut,@$value->customer_stage->name);
          $sheet->SetCellValue('E'.$urut,@$value->sales_inquery->name);
          $sheet->SetCellValue('F'.$urut,@$value->description_inquery);
          $sheet->SetCellValue('G'.$urut,@$value->status);
        }
      });
    })->download('xls');
  }


  public function contract_export(Request $request)
  {
    $wr="1=1";
    if ($request->customer_id) {
      $wr.=" AND quotations.customer_id = $request->customer_id";
    } else {
      if (auth()->user()->is_admin==0) {
        $wr.=" AND quotations.company_id = ".auth()->user()->company_id;
      }
    }
    $item = Quotation::with('customer','sales','customer_stage')->where('status_approve', 4)->where('is_active', 1)->where('is_hide', 0)->whereRaw($wr);

    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('is_active', $status) : $item;
    $customer_stage_id = $request->customer_stage_id;
    $customer_stage_id = $customer_stage_id != null ? $customer_stage_id : '';
    $item = $customer_stage_id != '' ? $item->where('customer_stage_id', $customer_stage_id) : $item;
    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $item = $start_date != '' && $end_date != ' ' ? $item->whereBetween('date_start_contract', [$start_date, $end_date]) : $item;

    $item = $item->select('quotations.*');
    $datatable = DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ui-sref=\"marketing.contract.show({id:$item->id})\"><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->addColumn('action_customer', function($item){
        $html="<a ui-sref=\"main.contract.show({id:$item->id})\"><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->rawColumns(['action','action_customer'])
      ->skipPaging()->make(true);

      $datatable = json_decode( json_encode($datatable));
      $data = $datatable->original->data;

      return Excel::create('Contract',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
      $sheet->SetCellValue('A1','Nama');
      $sheet->SetCellValue('B1','No. Quotation');
      $sheet->SetCellValue('C1','No. Kontrak');
      $sheet->SetCellValue('D1','Tanggal');
      $sheet->SetCellValue('E1','Berakhir');
      $sheet->SetCellValue('F1','Customer');
      $sheet->SetCellValue('G1','Sales');
      $sheet->SetCellValue('H1','Periode');
      $sheet->SetCellValue('I1','Status');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,@$value->name);
          $sheet->SetCellValue('B'.$urut,@$value->code);
          $sheet->SetCellValue('C'.$urut,@$value->no_contract);
          $sheet->SetCellValue('D'.$urut,@$value->date_start_contract);
          $sheet->SetCellValue('E'.$urut,@$value->date_end_contract);
          $sheet->SetCellValue('F'.$urut,@$value->customer->name);
          $sheet->SetCellValue('G'.$urut,@$value->sales->name);
          $sheet->SetCellValue('H'.$urut,@$value->send_type_name);
          $sheet->SetCellValue('I'.$urut,@$value->active_name);
        }
      });
    })->download('xls');
  }


  public function work_order_export(Request $request)
  {
      $wr="1=1";
    // if (isset($request->service_not_in)) {
    //   foreach ($request->service_not_in as $key => $value) {
    //     $wr.=" AND service_type_id != ".$value;
    //   }
    // }

    $start_date = $request->start_date;
    $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
    $wr .= $start_date != '' && $end_date != ' ' ? " AND work_orders.date BETWEEN '$start_date' AND '$end_date'" : '';
    if (isset($request->customer_id)) {
      $wr.=" AND work_orders.customer_id = $request->customer_id";
    }
    if ($request->is_not_invoice) {
      $wr.=" AND (quotations.bill_type = 1 AND jos.total_no_invoice > 0)";
    }

    if (isset($request->is_invoice)) {
      $wr.=" AND work_orders.is_invoice = $request->is_invoice";
    }
    if ($request->wo_done) {
      $wr.=" AND work_orders.status = 2";
    }
    if ($request->status) {
      $wr.=" AND work_orders.status = $request->status";
    }

    // if ($request->company_id) {
    //   $wr.=" AND work_orders.company_id = $request->company_id";
    // }
    if (auth()->user()->is_admin==0) {
      $wr.=" AND work_orders.company_id = ".auth()->user()->company_id;
    }
    else {
          if (isset($request->company_id)) {
            $wr.=" AND work_orders.company_id = $request->company_id";
          }

    }

    $item = WorkOrder::with('customer','company','quotation')
    ->leftJoin('quotations','quotations.id','=','work_orders.quotation_id')
    ->leftJoin(DB::raw("(select work_order_id, sum(IF(invoice_id is null,1,0)) as total_no_invoice from job_orders group by work_order_id) jos"),"jos.work_order_id","=","work_orders.id")
    ->leftJoin(DB::raw('(select jo.work_order_id,group_concat(distinct no_po_customer) as po_customer from job_orders as jo group by jo.work_order_id) jo'),'jo.work_order_id','work_orders.id')
    ->whereRaw($wr)
    ->selectRaw('work_orders.*, jos.total_no_invoice, jo.po_customer');

    $datatable = DataTables::of($item)
      ->editColumn('date', function($item){
        return dateView($item->date);
      })
      ->editColumn('status', function($item){
        $stt=[
          1=>'Proses',
          2=>'Selesai',
        ];
        return $stt[$item->status];
      })
      ->editColumn('no_bl', function($item){
        $str="";
        // $explode=explode(',',$item->no_bl);
        return $item->no_bl;
      })
      ->editColumn('aju_number', function($item){
        $str="";
        // $explode=explode(',',$item->aju_number);
        return $item->aju_number;
      })
      ->skipPaging()->make(true);

      $datatable = json_decode( json_encode($datatable));
      $data = $datatable->original->data;

      return Excel::create('Work Order',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
      $sheet->SetCellValue('A1','Cabang');
      $sheet->SetCellValue('B1','Tgl');
      $sheet->SetCellValue('C1','Kode');
      $sheet->SetCellValue('D1','Customer');
      $sheet->SetCellValue('E1','Nama Pekerjaan');
      $sheet->SetCellValue('F1','AJU');
      $sheet->SetCellValue('G1','BL');
      $sheet->SetCellValue('H1','Job Order');
      $sheet->SetCellValue('I1','Status');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,@$value->company->name);
          $sheet->SetCellValue('B'.$urut,@$value->date);
          $sheet->SetCellValue('C'.$urut,@$value->code);
          $sheet->SetCellValue('D'.$urut,@$value->customer->name);
          $sheet->SetCellValue('E'.$urut,@$value->name);
          $sheet->SetCellValue('F'.$urut,@$value->aju_number);
          $sheet->SetCellValue('G'.$urut,@$value->no_bl);
          $sheet->SetCellValue('H'.$urut,@$value->total_job_order);
          $sheet->SetCellValue('I'.$urut,@$value->status);
        }
      });
    })->download('xls');
  }

  public function activity_wo_export(Request $request)
  {
    $wr="1=1";
    if ($request->customer_id) {
      $wr.=" AND wo.customer_id = $request->customer_id";
    }
    if ($request->company_id) {
      $wr.=" AND wo.company_id = $request->company_id";
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and wo.date between '$start' and '$end'";
    }
    $item=DB::table('work_orders as wo')
    ->leftJoin('contacts','contacts.id','wo.customer_id')
    ->leftJoin(DB::raw("(select sum(distinct iv.grand_total) as grand_total,group_concat(distinct iv.code) as code,group_concat(distinct iv.date_invoice) as date_invoice, job_orders.work_order_id from invoices as iv left join invoice_details as ivd on ivd.header_id = iv.id left join job_orders on job_orders.id = ivd.job_order_id group by job_orders.work_order_id) as Y"),'Y.work_order_id','wo.id')
    ->leftJoin(DB::raw("(select sum(if(joc.type=1,joc.total_price,0)) as operasional,sum(if(joc.type=2,joc.total_price,0)) as reimburse, jo.work_order_id from job_order_costs as joc left join job_orders as jo on jo.id = joc.header_id where joc.status in (3,5,8) group by jo.work_order_id) as X"),'X.work_order_id','wo.id')
    ->whereRaw($wr)
    ->selectRaw("
      distinct
      wo.code as code_wo,
      wo.date as date_wo,
      ifnull(Y.grand_total,0) as invoice_price,
      ifnull(X.operasional,0) as operational_price,
      ifnull(X.reimburse,0) as talangan_price,
      Y.code as code_invoice,
      Y.date_invoice,
      contacts.name as customer,
      concat('') as description,
      if(Y.grand_total is not null,ifnull(Y.grand_total,0)-ifnull(X.operasional,0)-ifnull(X.reimburse,0),0) as profit,
      if(Y.grand_total is not null,round((ifnull(Y.grand_total,0)-ifnull(X.operasional,0)-ifnull(X.reimburse,0))/ifnull(Y.grand_total,0)*100,2),0) as presentase
    ");
    return DataTables::of($item)
      ->filterColumn('profit', function($query, $keyword) {
        $sql="if(Y.grand_total is not null,ifnull(Y.grand_total,0)-ifnull(X.operasional,0)-ifnull(X.reimburse,0),0) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->filterColumn('presentase', function($query, $keyword) {
        $sql="if(Y.grand_total is not null,round((ifnull(Y.grand_total,0)-ifnull(X.operasional,0)-ifnull(X.reimburse,0))/ifnull(Y.grand_total,0)*100,2),0) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->filterColumn('description', function($query, $keyword) {
        $sql="CONCAT('') like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->editColumn('date_wo', function($item){
        return dateView($item->date_wo);
      })
      ->editColumn('operational_price', function($item){
        return formatNumber($item->operational_price);
      })
      ->editColumn('talangan_price', function($item){
        return formatNumber($item->talangan_price);
      })
      ->editColumn('invoice_price', function($item){
        return formatNumber($item->invoice_price);
      })
      ->editColumn('profit', function($item){
        return formatNumber($item->profit);
      })
      ->editColumn('presentase', function($item){
        return formatNumber($item->presentase).' %';
      })
      ->make(true);
      return Excel::create('Work Order',function($excel) use ($data){
        $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No WO');
        $sheet->SetCellValue('B1','Tgl WO');
        $sheet->SetCellValue('C1','Customer');
        $sheet->SetCellValue('D1','Operasional');
        $sheet->SetCellValue('E1','Reimburse');
        $sheet->SetCellValue('F1','Invoice');
        $sheet->SetCellValue('G1','Profit');
        $sheet->SetCellValue('H1','Presentase');
        $sheet->SetCellValue('I1','No. Invoice');
        $sheet->SetCellValue('J1','Tgl Invoice');
        $sheet->SetCellValue('K1','Keterangan');
        $nomor = 0;
        
        foreach ($data as $i => $value) {
            $urut=$i+2;
            $nomor++;
            $sheet->SetCellValue('A'.$urut,@$value->wo->code);
            $sheet->SetCellValue('B'.$urut,@$value->wo->date);
            $sheet->SetCellValue('C'.$urut,@$value->contacts->name);
            $sheet->SetCellValue('D'.$urut,@$value->X->operasional);
            $sheet->SetCellValue('E'.$urut,@$value->X->reimburse);
            $sheet->SetCellValue('F'.$urut,@$value->Y->grand_total);
            $sheet->SetCellValue('G'.$urut,@$value->profit);
            $sheet->SetCellValue('H'.$urut,@$value->presentase);
            $sheet->SetCellValue('I'.$urut,@$value->Y->code);
            $sheet->SetCellValue('J'.$urut,@$value->Y->date_invoice);
            $sheet->SetCellValue('K'.$urut,@$value->description);
        }
      });
    })->download('xls');
  }

  public function user_management_export()
  {
    $data=DB::table('users')
    ->leftJoin('companies','companies.id','=','users.company_id')
    ->leftJoin('cities','cities.id','=','users.city_id')
    ->leftJoin('group_types','group_types.id','=','users.group_id')
    ->select('users.name',
      'users.email',
      'companies.name as cabang',
      'users.username',
      'cities.name as city',
      'users.last_login',
      'group_types.name as group',
      'users.is_admin'
      )
    ->orderBy('company_id','asc')
    ->get();

    return Excel::create('Semua User',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama');
        $sheet->SetCellValue('C1','Email');
        $sheet->SetCellValue('D1','Cabang');
        $sheet->SetCellValue('E1','Username');
        $sheet->SetCellValue('F1','Login Terakhir');
        $sheet->SetCellValue('G1','Group');
        $sheet->SetCellValue('H1','Admin');

        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          if($value->is_admin == 1){
            $admin  = "Ya";
          }else{
            $admin = "Tidak";
          }

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          $sheet->SetCellValue('C'.$urut,$value->email);
          $sheet->SetCellValue('D'.$urut,$value->cabang);
          $sheet->SetCellValue('E'.$urut,$value->username);
          $sheet->SetCellValue('F'.$urut,$value->last_login);
          $sheet->SetCellValue('G'.$urut,$value->group);
          $sheet->SetCellValue('H'.$urut,$admin);
        }
      });
    })->download('xls');
  }

  public function account_export()
  {

    $data=DB::table('accounts')
    ->leftJoin('account_types','account_types.id','=','accounts.type_id')
    ->leftJoin('companies','companies.id','=','accounts.company_id')
    ->where('accounts.is_base','1')
    ->orderBy('accounts.code')
    ->select('accounts.code',
      'accounts.id as id_account',
      'accounts.name as account',
      'accounts.description',
      'accounts.parent_id',
      'accounts.deep',
      'accounts.is_base',
      'accounts.jenis',
      'accounts.group_report',
      'account_types.name as tipe',
      'companies.name as cabang'
      )
    ->get();

    return Excel::create('Daftar Akun',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Nama Akun');
        $sheet->SetCellValue('D1','Keterangan');
        $sheet->SetCellValue('E1','Jenis');
        $sheet->SetCellValue('F1','Kelompok');
        $sheet->SetCellValue('G1','Tipe');
        $sheet->SetCellValue('H1','Kas Cabang');

        $nomor = 0;
        $jenis = "";
        $tipe = "";
        $spasi = "";
        $urut = 1;
        foreach ($data as $i => $value) {
          $urut++;
          $nomor++;
          if($value->jenis == 1){
            $jenis = "Debet";
          }else{
            $jenis = "Kredit";
          }

          if($value->group_report == 1){
            $kelompok = "Neraca";
          }else{
            $kelompok = "Laba-rugi";
          }

          if($value->deep == 0){
            $spasi = "";
          }else if($value->deep == 1){
            $spasi = "  ";
          }else if($value->deep == 2){
            $spasi = "    ";
          }

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$spasi . $value->account);
          $sheet->SetCellValue('D'.$urut,$value->description);
          $sheet->SetCellValue('E'.$urut,$jenis);
          $sheet->SetCellValue('F'.$urut,$kelompok);
          $sheet->SetCellValue('G'.$urut,$value->tipe);
          $sheet->SetCellValue('H'.$urut,$value->cabang);

          $data1=DB::table('accounts')
          ->leftJoin('account_types','account_types.id','=','accounts.type_id')
          ->leftJoin('companies','companies.id','=','accounts.company_id')
          ->where('accounts.is_base','0')
          ->where('accounts.parent_id',$value->id_account)
          ->orderBy('accounts.code')
          ->select('accounts.code',
            'accounts.id as id_account',
            'accounts.name as account',
            'accounts.description',
            'accounts.parent_id',
            'accounts.deep',
            'accounts.is_base',
            'accounts.jenis',
            'accounts.group_report',
            'account_types.name as tipe',
            'companies.name as cabang'
            )
          ->get();

          foreach ($data1 as $j => $value1) {
            $urut++;
            $nomor++;
            if($value1->jenis == 1){
              $jenis = "Debet";
            }else{
              $jenis = "Kredit";
            }

            if($value1->group_report == 1){
              $kelompok = "Neraca";
            }else{
              $kelompok = "Laba-rugi";
            }

            if($value1->deep == 0){
              $spasi = "";
            }else if($value1->deep == 1){
              $spasi = "  ";
            }else if($value1->deep == 2){
              $spasi = "    ";
            }

            $sheet->SetCellValue('A'.$urut,$nomor);
            $sheet->SetCellValue('B'.$urut,$value1->code);
            $sheet->SetCellValue('C'.$urut,$spasi . $value1->account);
            $sheet->SetCellValue('D'.$urut,$value1->description);
            $sheet->SetCellValue('E'.$urut,$jenis);
            $sheet->SetCellValue('F'.$urut,$kelompok);
            $sheet->SetCellValue('G'.$urut,$value1->tipe);
            $sheet->SetCellValue('H'.$urut,$value1->cabang);

          }

        }
      });
    })->download('xls');

  }

  public function region_export()
  {
    $data=DB::table('cities')
    ->leftJoin('provinces','provinces.id','=','cities.province_id')
    ->leftJoin('countries','countries.id','=','provinces.country_id')
    ->select('cities.id as city_id',
      'countries.name as negara',
      'provinces.name as provinsi',
      'cities.name as wilayah',
      'cities.type'
    )
    ->orderBy('negara, provinsi')
    ->get();

    return Excel::create('Semua Wilayah',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Negara');
        $sheet->SetCellValue('C1','Provinsi');
        $sheet->SetCellValue('D1','Wilayah');
        $sheet->SetCellValue('E1','Tipe');

        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->negara);
          $sheet->SetCellValue('C'.$urut,$value->provinsi);
          $sheet->SetCellValue('D'.$urut,$value->wilayah);
          $sheet->SetCellValue('E'.$urut,$value->type);
        }
      });
    })->download('xls');
  }

  public function route_cost_export()
  {
    $data=DB::table('route_costs')
    ->leftJoin('routes','routes.id','=','route_costs.route_id')
    ->leftJoin('commodities','commodities.id','=','route_costs.commodity_id')
    ->leftJoin('vehicle_types','vehicle_types.id','=','route_costs.vehicle_type_id')
    ->where('route_costs.is_container','=','0')
    ->select('routes.name as route',
      'commodities.name as commodity',
      'route_costs.cost as total_biaya',
      'route_costs.description as keterangan_header',
      'vehicle_types.name as vehicle_type'
    )
    ->orderBy('route_costs.id')
    ->get();

    $data_detail=DB::table('route_costs')

    ->leftJoin('routes','routes.id','=','route_costs.route_id')
    ->leftJoin('commodities','commodities.id','=','route_costs.commodity_id')
    ->leftJoin('vehicle_types','vehicle_types.id','=','route_costs.vehicle_type_id')
    ->leftJoin('route_cost_details','routes.id','=','route_costs.route_id')
    ->leftJoin('cost_types','cost_types.id','=','route_cost_details.cost_type_id')
    ->where('route_costs.is_container','=','0')
    ->select('routes.name as route',
      'commodities.name as commodity',
      'route_costs.cost as total_biaya',
      'route_costs.description as keterangan header',
      'vehicle_types.name as vehicle_type',
      'cost_types.name as nama_biaya',
      'route_cost_details.cost as biaya_detail',
      'route_cost_details.description as keterangan_detail',
      'route_cost_details.is_internal'
    )
    ->orderBy('route_costs.id')
    ->get();

    // dd($data_detail);
    return Excel::create('Semua Biaya Ritase',function($excel) use ($data,$data_detail){

      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Trayek');
        $sheet->SetCellValue('C1','Komoditas');
        $sheet->SetCellValue('D1','Total Biaya');
        $sheet->SetCellValue('E1','Keterangan');
        $sheet->SetCellValue('E1','Tipe Kendaraan');

        $nomor = 0;
        $urut = 1;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut++;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->route);
          $sheet->SetCellValue('C'.$urut,$value->commodity);
          $sheet->SetCellValue('D'.$urut,$value->total_biaya);
          $sheet->SetCellValue('E'.$urut,$value->keterangan_header);
          $sheet->SetCellValue('F'.$urut,$value->vehicle_type);
        }
      });

      $excel->sheet('detail', function($sheet) use ($data_detail){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Trayek');
        $sheet->SetCellValue('C1','Komoditas');
        $sheet->SetCellValue('D1','Total Biaya');
        $sheet->SetCellValue('E1','Keterangan Header');
        $sheet->SetCellValue('F1','Tipe Kendaraan');
        $sheet->SetCellValue('G1','Nama Biaya');
        $sheet->SetCellValue('H1','Biaya');
        $sheet->SetCellValue('I1','Pengeluaran');
        $sheet->SetCellValue('J1','Keterangan Biaya');

        $nomor = 0;
        $urut = 1;
        $pengeluaran = "";



        foreach ($data_detail as $i => $value) {
          $urut++;
          $nomor++;

          if($value->is_internal == 1){
            $pengeluaran = "Internal";
          }else{
            $pengeluaran = "Eksternal";
          }

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->route);
          $sheet->SetCellValue('C'.$urut,$value->commodity);
          $sheet->SetCellValue('D'.$urut,$value->total_biaya);
          $sheet->SetCellValue('E'.$urut,$value->keterangan_detail);
          $sheet->SetCellValue('F'.$urut,$value->vehicle_type);
          $sheet->SetCellValue('G'.$urut,$value->nama_biaya);
          $sheet->SetCellValue('H'.$urut,$value->biaya_detail);
          $sheet->SetCellValue('I'.$urut,$value->vehicle_type);
          $sheet->SetCellValue('J'.$urut,$pengeluaran);
        }

      });

    })->download('xls');
  }

  public function container_cost_export()
  {
    $data=DB::table('route_costs')
    ->leftJoin('routes','routes.id','=','route_costs.route_id')
    ->leftJoin('commodities','commodities.id','=','route_costs.commodity_id')
    ->leftJoin('container_types','container_types.id','=','route_costs.container_type_id')
    ->where('route_costs.is_container','=','1')
    ->select('routes.name as route',
      'commodities.name as commodity',
      'route_costs.cost as total_biaya',
      'route_costs.description as keterangan_header',
      'container_types.name as container_type'
    )
    ->orderBy('route_costs.id')
    ->get();

    $data_detail=DB::table('route_costs')

    ->leftJoin('routes','routes.id','=','route_costs.route_id')
    ->leftJoin('commodities','commodities.id','=','route_costs.commodity_id')
    ->leftJoin('container_types','container_types.id','=','route_costs.container_type_id')
    ->leftJoin('route_cost_details','routes.id','=','route_costs.route_id')
    ->leftJoin('cost_types','cost_types.id','=','route_cost_details.cost_type_id')
    ->where('route_costs.is_container','=','1')
    ->select('routes.name as route',
      'commodities.name as commodity',
      'route_costs.cost as total_biaya',
      'route_costs.description as keterangan header',
      'container_types.name as container_type',
      'cost_types.name as nama_biaya',
      'route_cost_details.cost as biaya_detail',
      'route_cost_details.description as keterangan_detail',
      'route_cost_details.is_internal'
    )
    ->orderBy('route_costs.id')
    ->get();

    // dd($data_detail);
    return Excel::create('Semua Biaya Kontainer',function($excel) use ($data,$data_detail){

      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Trayek');
        $sheet->SetCellValue('C1','Komoditas');
        $sheet->SetCellValue('D1','Total Biaya');
        $sheet->SetCellValue('E1','Keterangan');
        $sheet->SetCellValue('E1','Tipe Kontainer');

        $nomor = 0;
        $urut = 1;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut++;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->route);
          $sheet->SetCellValue('C'.$urut,$value->commodity);
          $sheet->SetCellValue('D'.$urut,$value->total_biaya);
          $sheet->SetCellValue('E'.$urut,$value->keterangan_header);
          $sheet->SetCellValue('F'.$urut,$value->container_type);
        }
      });

      $excel->sheet('detail', function($sheet) use ($data_detail){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Trayek');
        $sheet->SetCellValue('C1','Komoditas');
        $sheet->SetCellValue('D1','Total Biaya');
        $sheet->SetCellValue('E1','Keterangan Header');
        $sheet->SetCellValue('F1','Tipe Kontainer');
        $sheet->SetCellValue('G1','Nama Biaya');
        $sheet->SetCellValue('H1','Biaya');
        $sheet->SetCellValue('I1','Pengeluaran');
        $sheet->SetCellValue('J1','Keterangan Biaya');

        $nomor = 0;
        $urut = 1;
        $pengeluaran = "";



        foreach ($data_detail as $i => $value) {
          $urut++;
          $nomor++;

          if($value->is_internal == 1){
            $pengeluaran = "Internal";
          }else{
            $pengeluaran = "Eksternal";
          }

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->route);
          $sheet->SetCellValue('C'.$urut,$value->commodity);
          $sheet->SetCellValue('D'.$urut,$value->total_biaya);
          $sheet->SetCellValue('E'.$urut,$value->keterangan_detail);
          $sheet->SetCellValue('F'.$urut,$value->container_type);
          $sheet->SetCellValue('G'.$urut,$value->nama_biaya);
          $sheet->SetCellValue('H'.$urut,$value->biaya_detail);
          $sheet->SetCellValue('I'.$urut,$value->container_type);
          $sheet->SetCellValue('J'.$urut,$pengeluaran);
        }

      });

    })->download('xls');
  }

  public function route_export()
  {
    $data=DB::table('routes')
    ->leftJoin('companies','routes.company_id','=','companies.id')
    ->leftJoin('cities as city_from','routes.city_from','=','city_from.id')
    ->leftJoin('cities as city_to','routes.city_to','=','city_to.id')
    ->select(
      'routes.code',
      'routes.name',
      DB::raw("CONCAT(routes.distance,' Km') as jarak"),
      DB::raw("CONCAT(routes.duration,(IF(routes.type_satuan=1,' Jam',' Hari'))) as waktu"),
      DB::raw("CONCAT(UCASE(city_from.type),' ',UCASE(city_from.name)) as city_from"),
      DB::raw("CONCAT(UCASE(city_to.type),' ',UCASE(city_to.name)) as city_to"),
      'companies.name as cabang'
      )
    ->orderBy('company_id','asc')
    ->get();

    return Excel::create('Semua Trayek',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','Cabang');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Nama Trayek');
        $sheet->SetCellValue('D1','Kota Asal');
        $sheet->SetCellValue('E1','Kota Tujuan');
        $sheet->SetCellValue('F1','Jarak');
        $sheet->SetCellValue('G1','Waktu Tempuh');

        foreach ($data as $i => $value) {
          $urut=$i+2;
          $sheet->SetCellValue('A'.$urut,$value->cabang);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->name);
          $sheet->SetCellValue('D'.$urut,$value->city_from);
          $sheet->SetCellValue('E'.$urut,$value->city_to);
          $sheet->SetCellValue('F'.$urut,$value->jarak);
          $sheet->SetCellValue('G'.$urut,$value->waktu);
        }
      });
    })->download('xls');
  }

    public function kontak_export()
  {
    $data=DB::table('contacts')
    ->leftJoin('companies','contacts.company_id','=','companies.id')
    ->leftJoin('vendor_types','contacts.vendor_type_id','=','vendor_types.id')
    ->leftJoin('cities','contacts.city_id','=','cities.id')
     ->select('contacts.code',
      'contacts.name',
      'companies.name as namacabang',
      'cities.name as namakota',
      'contacts.address',
      'contacts.phone',
      'contacts.is_pegawai',
      'contacts.is_investor',
      'contacts.is_pelanggan',
      'contacts.is_asuransi',
      'contacts.is_supplier',
      'contacts.is_depo_bongkar',
      'contacts.is_helper',
      'contacts.is_driver',
      'contacts.is_vendor',
      'contacts.is_sales',
      'contacts.is_kurir',
      'contacts.is_pengirim',
      'contacts.is_penerima'
    )
    ->orderBy('contacts.id','asc')
    ->get();

    return Excel::create('Semua Kontak',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Nama Kontak');
        $sheet->SetCellValue('E1','Alamat');
        $sheet->SetCellValue('F1','Kota');
        $sheet->SetCellValue('G1','Telepon');
        $sheet->SetCellValue('H1','Jenis Kontak');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $status="";
          $urut=$i+2;

        if($value->is_pegawai == 1){
          $status= $status."Pegawai, ";
        }
        if($value->is_investor == 1){
          $status= $status."Investor, ";
        }
        if($value->is_pelanggan == 1){
          $status= $status."Custumer, ";
        }
        if($value->is_asuransi == 1){
          $status= $status."Asuransi, ";
        }
        if($value->is_supplier == 1){
          $status= $status."Supplier, ";
        }
        if($value->is_depo_bongkar == 1){
          $status= $status."Depo Bongkar, ";
        }
        if($value->is_helper == 1){
          $status= $status."Helper, ";
        }
        if($value->is_driver == 1){
          $status= $status."Driver, ";
        }
        if($value->is_vendor == 1){
          $status= $status."Vendor, ";
        }
        if($value->is_sales == 1){
          $status= $status."Sales, ";
        }
        if($value->is_kurir == 1){
          $status= $status."Kurir, ";
        }
        if($value->is_pengirim == 1){
          $status= $status."Pengirim, ";
        }
        if($value->is_penerima == 1){
          $status= $status."Penerima, ";
        }

          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->namacabang);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->name);
          $sheet->SetCellValue('E'.$urut,$value->address);
          $sheet->SetCellValue('F'.$urut,$value->namakota);
          $sheet->SetCellValue('G'.$urut,$value->phone);
          $sheet->SetCellValue('H'.$urut,$status);
          $nomer++;
        }
      });
    })->download('xls');
  }

    public function vendor_export()
  {
    $data=DB::table('contacts')
    ->leftJoin('companies','contacts.company_id','=','companies.id')
    ->leftJoin('vendor_types','contacts.vendor_type_id','=','vendor_types.id')
    ->leftJoin('cities','contacts.city_id','=','cities.id')
    ->where('contacts.is_vendor','1')
    ->where('contacts.vendor_status_approve','2')
    ->select('contacts.code',
      'contacts.name',
      'companies.name as namacabang',
      'cities.name as namakota',
      'contacts.address',
      'contacts.phone',
      'contacts.email',
      'contacts.is_vendor',
      'vendor_types.name as jenisvendor'


    )
    ->orderBy('contacts.id','asc')
    ->get();

    return Excel::create('Semua Vendor',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Nama Kontak');
        $sheet->SetCellValue('E1','Alamat');
        $sheet->SetCellValue('F1','Kota');
        $sheet->SetCellValue('G1','Telepon');
        $sheet->SetCellValue('H1','Email');
        $sheet->SetCellValue('I1','Jenis Vendor');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $urut=$i+2;


          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->namacabang);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->name);
          $sheet->SetCellValue('E'.$urut,$value->address);
          $sheet->SetCellValue('F'.$urut,$value->namakota);
          $sheet->SetCellValue('G'.$urut,$value->phone);
          $sheet->SetCellValue('H'.$urut,$value->email);
          $sheet->SetCellValue('I'.$urut,$value->jenisvendor);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function driver_export()
  {
    $data=DB::table('contacts')
    ->leftJoin('companies','contacts.company_id','=','companies.id')
    ->leftJoin('vendor_types','contacts.vendor_type_id','=','vendor_types.id')
    ->leftJoin('cities','contacts.city_id','=','cities.id')
    ->leftJoin('banks','contacts.rek_bank_id','=','banks.id')
    ->where('contacts.is_driver','1')
    ->select('contacts.code',
      'contacts.name',
      'companies.name as namacabang',
      'cities.name as namakota',
      'contacts.address',
      'contacts.phone',
      'contacts.email',
      'contacts.driver_status',
      'contacts.npwp',
      'contacts.pegawai_no',
      'contacts.description',
      'contacts.rek_no',
      'contacts.rek_milik',
      'contacts.rek_cabang',
      'banks.name as namabank'


    )
    ->orderBy('contacts.id','asc')
    ->get();

    return Excel::create('Semua Driver',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Nama Driver');
        $sheet->SetCellValue('E1','Alamat');
        $sheet->SetCellValue('F1','Kota');
        $sheet->SetCellValue('G1','Telepon');
        $sheet->SetCellValue('H1','Email');
        $sheet->SetCellValue('I1','Status Driver');
        $sheet->SetCellValue('J1','No Pegawai');
        $sheet->SetCellValue('K1','NPWP');
        $sheet->SetCellValue('L1','Keterangan');
        $sheet->SetCellValue('M1','No Rek Bank');
        $sheet->SetCellValue('N1','Pemilik Rek');
        $sheet->SetCellValue('O1','Bank');
        $sheet->SetCellValue('P1','Rek Cabang');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          if ($value->driver_status==1){
            $status='Utama';
          }else if($value->driver_status==2){
            $status='Cadangan';
          }else if($value->driver_status==3){
            $status='Helper';
          }else{
            $status='External';
          }

          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->namacabang);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->name);
          $sheet->SetCellValue('E'.$urut,$value->address);
          $sheet->SetCellValue('F'.$urut,$value->namakota);
          $sheet->SetCellValue('G'.$urut,$value->phone);
          $sheet->SetCellValue('H'.$urut,$value->email);
          $sheet->SetCellValue('I'.$urut,$status);
          $sheet->SetCellValue('J'.$urut,$value->pegawai_no);
          $sheet->SetCellValue('K'.$urut,$value->npwp);
          $sheet->SetCellValue('L'.$urut,$value->description);
          $sheet->SetCellValue('M'.$urut,$value->rek_no);
          $sheet->SetCellValue('N'.$urut,$value->rek_milik);
          $sheet->SetCellValue('O'.$urut,$value->rek_cabang);
          $sheet->SetCellValue('P'.$urut,$value->namabank);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function laporan_penerimaan_barang_export(Request $request)
  {
   $item = DB::table('stock_transactions_report')
    ->leftJoin('items as i', 'item_id', 'i.id')
    ->leftJoin('categories as c', 'category_id', 'c.id')
    ->leftJoin('warehouses as w', 'warehouse_id', 'w.id');


    $warehouse_id = $request->warehouse_id;
    $warehouse_id = $warehouse_id != null ? $warehouse_id : '';
    $item = $warehouse_id != '' ? $item->where('warehouse_id', $warehouse_id) : $item;
    
    $start_date = $request->start_date;
    $start_date = $start_date != null ? new DateTime($start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? new DateTime($end_date) : '';
    $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_transaction', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;
    
    $data = $item->selectRaw('stock_transactions_report.*, barcode, i.name AS item_name, c.name AS category_name, w.name AS warehouse_name')->orderBy('date_transaction', 'DESC')->get();

    return Excel::create('Laporan Penerimaan Barang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Tanggal');
        $sheet->SetCellValue('C1','Gudang');
        $sheet->SetCellValue('D1','Kategori');
        $sheet->SetCellValue('E1','Nama Barang');
        $sheet->SetCellValue('F1','Barcode');
        $sheet->SetCellValue('G1','Qty Masuk');
        $sheet->SetCellValue('H1','Qty Keluar');
        $sheet->SetCellValue('I1','Stok');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          

          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->date_transaction);
          $sheet->SetCellValue('C'.$urut,$value->warehouse_name);
          $sheet->SetCellValue('D'.$urut,$value->category_name);
          $sheet->SetCellValue('E'.$urut,$value->item_name);
          $sheet->SetCellValue('F'.$urut,$value->barcode);
          $sheet->SetCellValue('G'.$urut,$value->qty_masuk);
          $sheet->SetCellValue('H'.$urut,$value->qty_keluar);
          $sheet->SetCellValue('I'.$urut,$value->jumlah_stok);
          $nomer++;
        }
      });
    })->download('xls');
  }

    public function customer_export()
  {
    $data=DB::table('contacts')
    ->leftJoin('companies','contacts.company_id','=','companies.id')
    ->leftJoin('vendor_types','contacts.vendor_type_id','=','vendor_types.id')
    ->leftJoin('cities','contacts.city_id','=','cities.id')
    ->leftJoin('banks','contacts.rek_bank_id','=','banks.id')
    ->where('contacts.is_pelanggan','1')
    ->select('contacts.code',
      'contacts.name',
      'companies.name as namacabang',
      'cities.name as namakota',
      'contacts.address',
      'contacts.phone',
      'contacts.email',
      'contacts.fax',
      'contacts.contact_person',
      'contacts.contact_person_email',
      'contacts.contact_person_no',
      'contacts.term_of_payment',
      'contacts.limit_piutang',
      'contacts.limit_hutang',
      'contacts.npwp',
      'contacts.pegawai_no',
      'contacts.description',
      'contacts.rek_no',
      'contacts.rek_milik',
      'contacts.rek_cabang',
      'banks.name as namabank'


    )
    ->orderBy('contacts.id','asc')
    ->get();

    return Excel::create('Semua Customer',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Nama Driver');
        $sheet->SetCellValue('E1','Alamat');
        $sheet->SetCellValue('F1','Kota');
        $sheet->SetCellValue('G1','Telepon');
        $sheet->SetCellValue('H1','Fax');
        $sheet->SetCellValue('I1','Email');
        $sheet->SetCellValue('J1','Contact Person');
        $sheet->SetCellValue('K1','Email Contact Person');
        $sheet->SetCellValue('L1','No Contact Person');
        $sheet->SetCellValue('M1','No Pegawai');
        $sheet->SetCellValue('N1','TOP');
        $sheet->SetCellValue('O1','Limit Piutang');
        $sheet->SetCellValue('P1','Limit Hutang');
        $sheet->SetCellValue('Q1','NPWP');
        $sheet->SetCellValue('R1','Keterangan');
        $sheet->SetCellValue('S1','No Rek Bank');
        $sheet->SetCellValue('T1','Pemilik Rek');
        $sheet->SetCellValue('U1','Bank');
        $sheet->SetCellValue('V1','Rek Cabang');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $urut=$i+2;


          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->namacabang);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->name);
          $sheet->SetCellValue('E'.$urut,$value->address);
          $sheet->SetCellValue('F'.$urut,$value->namakota);
          $sheet->SetCellValue('G'.$urut,$value->phone);
          $sheet->SetCellValue('H'.$urut,$value->fax);
          $sheet->SetCellValue('I'.$urut,$value->email);
          $sheet->SetCellValue('J'.$urut,$value->contact_person);
          $sheet->SetCellValue('K'.$urut,$value->contact_person_email);
          $sheet->SetCellValue('L'.$urut,$value->contact_person_no);
          $sheet->SetCellValue('M'.$urut,$value->pegawai_no);
          $sheet->SetCellValue('N'.$urut,$value->term_of_payment);
          $sheet->SetCellValue('O'.$urut,$value->limit_piutang);
          $sheet->SetCellValue('P'.$urut,$value->limit_hutang);
          $sheet->SetCellValue('Q'.$urut,$value->npwp);
          $sheet->SetCellValue('R'.$urut,$value->description);
          $sheet->SetCellValue('S'.$urut,$value->rek_no);
          $sheet->SetCellValue('T'.$urut,$value->rek_milik);
          $sheet->SetCellValue('U'.$urut,$value->namabank);
          $sheet->SetCellValue('V'.$urut,$value->rek_cabang);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function container_export(Request $request)
  {
    $data = OperationalApiController::container_query($request)->get();

    return Excel::create('Container',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','No Booking');
        $sheet->SetCellValue('D1','No Container');
        $sheet->SetCellValue('E1','Nama Kapal');
        $sheet->SetCellValue('F1','Tipe Container');
        $sheet->SetCellValue('G1','Tgl Booking');
        $sheet->SetCellValue('H1','Tgl Bongkar');
        $sheet->SetCellValue('I1','Tgl Muat');
        $sheet->SetCellValue('J1','Seal');
        $sheet->SetCellValue('K1','Jml Colly');
        $sheet->SetCellValue('L1','Kubikasi');
        $sheet->SetCellValue('M1','Tonase');
        $sheet->SetCellValue('N1','FCL/LCL');
        $sheet->SetCellValue('O1','Workorder');
        $sheet->SetCellValue('P1','Komoditas');

        $nomer = 1;
        foreach ($data as $i => $value) {

          $urut=$i+2;
          if($value->is_fcl==1){
             $fcl="FCL";
          }else{
             $fcl="LCL";
          }


          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->company->name);
          $sheet->SetCellValue('C'.$urut,$value->booking_number);
          $sheet->SetCellValue('D'.$urut,$value->container_no);
          $sheet->SetCellValue('E'.$urut,$value->voyage_schedule->vessel->name);
          $sheet->SetCellValue('F'.$urut,$value->container_type->name);
          $sheet->SetCellValue('G'.$urut,$value->booking_date);
          $sheet->SetCellValue('H'.$urut,$value->stripping);
          $sheet->SetCellValue('I'.$urut,$value->stuffing);
          $sheet->SetCellValue('J'.$urut,$value->seal_no);
          $sheet->SetCellValue('K'.$urut,$value->total_item);
          $sheet->SetCellValue('L'.$urut,$value->total_volume);
          $sheet->SetCellValue('M'.$urut,$value->total_tonase);
          $sheet->SetCellValue('N'.$urut,$fcl);
          $sheet->SetCellValue('O'.$urut,$value->code);
          $sheet->SetCellValue('P'.$urut,$value->commodity);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function jadwalkapal_export(Request $request)
  {
    $data = OperationalApiController::voyage_schedule_query($request)
        ->get();

    return Excel::create('Jadwal Kapal',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama Kapal');
        $sheet->SetCellValue('C1','Voyage');
        $sheet->SetCellValue('D1','ETD');
        $sheet->SetCellValue('E1','ETA');
        $sheet->SetCellValue('F1','Jumlah Container');
        $sheet->SetCellValue('G1','POL');
        $sheet->SetCellValue('H1','POD');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $status="";
          $urut=$i+2;
          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->vessel->name);
          $sheet->SetCellValue('C'.$urut,$value->voyage);
          $sheet->SetCellValue('D'.$urut,$value->etd);
          $sheet->SetCellValue('E'.$urut,$value->eta);
          $sheet->SetCellValue('F'.$urut,$value->total_container);
          $sheet->SetCellValue('G'.$urut,$value->pol->name);
          $sheet->SetCellValue('H'.$urut,$value->pod->name);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function joborder_export()
  {
    $data=DB::table('job_orders')
    ->leftJoin('companies','job_orders.company_id','=','companies.id')
    ->leftJoin('contacts','job_orders.customer_id','=','contacts.id')
    ->leftJoin('routes','job_orders.route_id','=','routes.id')
    ->leftJoin('service_types','job_orders.service_type_id','=','service_types.id')
    ->leftJoin('services','job_orders.service_id','=','services.id')
    ->leftJoin('contacts as pengirim','job_orders.sender_id','=','pengirim.id')
    ->leftJoin('contacts as penerima','job_orders.receiver_id','=','penerima.id')
    ->leftJoin('quotations','job_orders.quotation_id','=','quotations.id')
    ->leftJoin('quotation_details','job_orders.quotation_detail_id','=','quotation_details.id')
    ->leftJoin('contacts as tertagih','job_orders.collectible_id','=','tertagih.id')
    ->leftJoin('work_orders','job_orders.work_order_id','=','work_orders.id')
    ->leftJoin('invoices','job_orders.invoice_id','=','invoices.id')
    ->leftJoin('receivables','job_orders.receivable_id','=','receivables.id')
    ->leftJoin('vehicle_types','job_orders.vehicle_type_id','=','vehicle_types.id')
    ->leftJoin('commodities','job_orders.commodity_id','=','commodities.id')
    ->leftJoin('kpi_statuses','job_orders.kpi_id','=','kpi_statuses.id')
    ->leftJoin('work_order_details','job_orders.work_order_detail_id','=','work_order_details.id')
     ->select('work_orders.code as kodewo',
      'job_orders.code',
      'job_orders.no_bl',
      'job_orders.aju_number',
      'contacts.name as namacustomer',
      DB::raw("CONCAT(penerima.name, ', ' ,penerima.address) as namapenerima"),
      DB::raw("CONCAT(tertagih.name, ', ' ,tertagih.address) as namatertagih"),

      'job_orders.created_at',
      'job_orders.shipment_date',
      'job_orders.description',
       DB::raw("CONCAT(services.name, ', ' ,service_types.name) as jenisservis"),
      'job_orders.reff_no',
      'job_orders.docs_no',
      'job_orders.docs_reff_no',
      'job_orders.total_price',
      'kpi_statuses.name as status'

    )
    ->orderBy('job_orders.id','desc')
    ->get();

    return Excel::create('Job Orders',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Workorder');
        $sheet->SetCellValue('C1','Kode JobOrder');
        $sheet->SetCellValue('D1','No BL');
        $sheet->SetCellValue('E1','No Aju');
        $sheet->SetCellValue('F1','Customer');
        $sheet->SetCellValue('G1','Penerima');
        $sheet->SetCellValue('H1','Tertagih');
        $sheet->SetCellValue('I1','Waktu Input');
        $sheet->SetCellValue('J1','Tanggal Pengiriman');
        $sheet->SetCellValue('K1','Keterangan');
        $sheet->SetCellValue('L1','Jenis Layanan');
        $sheet->SetCellValue('M1','No Reff');
        $sheet->SetCellValue('N1','No Document');
        $sheet->SetCellValue('O1','No Reff Document');
        $sheet->SetCellValue('P1','Total Tarif');
        $sheet->SetCellValue('Q1','Status');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $status="";
          $urut=$i+2;



          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->kodewo);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->no_bl);
          $sheet->SetCellValue('E'.$urut,$value->aju_number);
          $sheet->SetCellValue('F'.$urut,$value->namacustomer);
          $sheet->SetCellValue('G'.$urut,$value->namapenerima);
          $sheet->SetCellValue('H'.$urut,$value->namatertagih);
          $sheet->SetCellValue('I'.$urut,$value->created_at);
          $sheet->SetCellValue('J'.$urut,$value->shipment_date);
          $sheet->SetCellValue('K'.$urut,$value->description);
          $sheet->SetCellValue('L'.$urut,$value->jenisservis);
          $sheet->SetCellValue('M'.$urut,$value->reff_no);
          $sheet->SetCellValue('N'.$urut,$value->docs_no);
          $sheet->SetCellValue('O'.$urut,$value->docs_reff_no);
          $sheet->SetCellValue('P'.$urut,$value->total_price);
          $sheet->SetCellValue('Q'.$urut,$value->status);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function invoicejual_export(Request $request)
  {
    
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND invoices.company_id = ".auth()->user()->company_id;
    }
    if ($request->customer_id) {
      $wr.=" and invoices.customer_id = $request->customer_id";
    }

    $item = Invoice::with('customer','company')->whereRaw($wr);

    // Filter customer, wilayah, status, dan periode
    $tgl_awal = $request->tgl_awal;
    $tgl_awal = $tgl_awal != null ? preg_replace('/([0-9]+)-([0-9]+)-([0-9]+)/', '$3-$2-$1', $tgl_awal) : '';
    $tgl_akhir = $request->tgl_akhir;
    $tgl_akhir = $tgl_akhir != null ? preg_replace('/([0-9]+)-([0-9]+)-([0-9]+)/', '$3-$2-$1', $tgl_akhir) : '';
    $item = $tgl_awal != '' && $tgl_akhir != '' ? $item->whereBetween('date_invoice', [$tgl_awal, $tgl_akhir]) : $item; 

    $company_id = $request->company_id;
    $company_id = $company_id != null ? $company_id : '';
    $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;
    $customer_id = $request->customer_id;
    $customer_id = $customer_id != null ? $customer_id : '';
    $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status', $status) : $item;

    $item = $item->select('invoices.*',DB::raw("(grand_total+grand_total_additional) as total"));

    $result = DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational.invoice_customer.detail')\" ui-sref='operational.invoice_jual.show({id:$item->id})' ><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('operational.invoice_customer.delete')\" ng-click='deletes($item->id)' ><span class='fa fa-trash'></span></a>";
        return $html;
      })
      ->addColumn('action_customer', function($item){
        $html="<a ui-sref='main.invoice.show({id:$item->id})' ><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->editColumn('created_at', function($item){
        return dateView($item->created_at);
      })
      ->editColumn('total', function($item){
        return formatNumber($item->total);
      })
      ->filterColumn('total', function($query, $keyword) {
          $sql = "(grand_total+grand_total_additional) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
          })
      ->editColumn('status', function($item){
        $stt=[
          1=>'Diajukan',
          2=>'Disetujui',
          3=>'Invoice',
          4=>'Terbayar Sebagian',
          5=>'Lunas',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action','action_customer'])
      ->skipPaging()->make(true);

    $result_encoded = json_decode( json_encode($result) );
    $data = $result_encoded->original->data;
    
    return Excel::create('Invoice Jual',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Kode Invoice');
        $sheet->SetCellValue('D1','Tanggal');
        $sheet->SetCellValue('E1','Customer');
        $sheet->SetCellValue('F1','Total');
        $sheet->SetCellValue('G1','Status');

        $nomer = 1;
        foreach ($data as $i => $value) {
          $status="";
          $urut=$i+2;



          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->company->name);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->date_invoice);
          $sheet->SetCellValue('E'.$urut,$value->customer->name);
          $sheet->SetCellValue('F'.$urut,$value->total);
          $sheet->SetCellValue('G'.$urut,$value->status);
          $nomer++;
        }
      });
    })->download('xls');
  }

    public function PL_FTL_export()
  {
    $data=DB::table('manifests')
    ->leftJoin('companies','manifests.company_id','=','companies.id')
    ->leftJoin('vehicle_types','manifests.vehicle_type_id','=','vehicle_types.id')
    ->leftJoin('container_types','manifests.container_type_id','=','container_types.id')
    ->leftJoin('routes','manifests.route_id','=','routes.id')
    ->leftJoin('modas','manifests.moda_id','=','modas.id')
    ->leftJoin('vehicles','manifests.vehicle_id','=','vehicles.id')
    ->leftJoin('contacts as driver','manifests.driver_id','=','driver.id')
    ->leftJoin('contacts as helper','manifests.helper_id','=','helper.id')
    ->leftJoin('containers','manifests.container_id','=','containers.id')
    ->leftJoin('delivery_order_drivers','manifests.delivery_order_id','=','delivery_order_drivers.id')
    ->where('manifests.is_container','=','0')
     ->select('companies.name as namacabang',
      'manifests.date_manifest',
      'vehicle_types.name as tipekendaraan',
      'manifests.reff_no',
      'manifests.is_full',
      'manifests.description',
      'routes.name as namatrayek',
      'modas.name as namamoda',
      'manifests.depart',
      'manifests.arrive',
      'containers.container_no',
      'manifests.status',
      'manifests.status_cost',
      'driver.name as namadriver',
      'vehicles.nopol'

    )
    ->orderBy('manifests.id','asc')
    ->get();

    return Excel::create('PackingList FTL/LTL',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Tipe Kendaraan');
        $sheet->SetCellValue('E1','No Reff');
        $sheet->SetCellValue('F1','Full Campuran');
        $sheet->SetCellValue('G1','Keterangan');
        $sheet->SetCellValue('H1','Trayek');
        $sheet->SetCellValue('I1','Moda');
        $sheet->SetCellValue('J1','Waktu Berangkat');
        $sheet->SetCellValue('K1','Waktu Sampai');
        $sheet->SetCellValue('L1','No Container');
        $sheet->SetCellValue('M1','Status');
        $sheet->SetCellValue('N1','Kendaraan');
        $sheet->SetCellValue('O1','Driver');
        $sheet->SetCellValue('P1','Keterangan Biaya');

        $nomer = 1;
        foreach ($data as $i => $value) {
          if($value->is_full==1){
             $full="Full";
          }else{
             $full="Campuran";
          }

          if($value->status==1){
             $status="Terjadwal";
          }else if($value->status==2){
             $status="Berangkat";
          }else if($value->status==3){
             $status="Sampai";
          }else if($value->status==4){
             $status="Selesai";
          }

          if($value->status_cost==1){
            $statusbiaya="Biaya Generate";
          }else{
            $statusbiaya="";
          }


          $urut=$i+2;



          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->namacabang);
          $sheet->SetCellValue('C'.$urut,$value->date_manifest);
          $sheet->SetCellValue('D'.$urut,$value->tipekendaraan);
          $sheet->SetCellValue('E'.$urut,$value->reff_no);
          $sheet->SetCellValue('F'.$urut,$full);
          $sheet->SetCellValue('G'.$urut,$value->description);
          $sheet->SetCellValue('H'.$urut,$value->namatrayek);
          $sheet->SetCellValue('I'.$urut,$value->namamoda);
          $sheet->SetCellValue('J'.$urut,$value->depart);
          $sheet->SetCellValue('K'.$urut,$value->arrive);
          $sheet->SetCellValue('L'.$urut,$value->container_no);
          $sheet->SetCellValue('M'.$urut,$status);
          $sheet->SetCellValue('N'.$urut,$value->nopol);
          $sheet->SetCellValue('O'.$urut,$value->namadriver);
          $sheet->SetCellValue('P'.$urut,$statusbiaya);
          $nomer++;
        }
      });
    })->download('xls');
  }

    public function PL_FCL_export()
  {
    $data=DB::table('manifests')
    ->leftJoin('companies','manifests.company_id','=','companies.id')
    ->leftJoin('vehicle_types','manifests.vehicle_type_id','=','vehicle_types.id')
    ->leftJoin('container_types','manifests.container_type_id','=','container_types.id')
    ->leftJoin('routes','manifests.route_id','=','routes.id')
    ->leftJoin('modas','manifests.moda_id','=','modas.id')
    ->leftJoin('vehicles','manifests.vehicle_id','=','vehicles.id')
    ->leftJoin('contacts as driver','manifests.driver_id','=','driver.id')
    ->leftJoin('contacts as helper','manifests.helper_id','=','helper.id')
    ->leftJoin('containers','manifests.container_id','=','containers.id')
    ->leftJoin('delivery_order_drivers','manifests.delivery_order_id','=','delivery_order_drivers.id')
    ->where('manifests.is_container','=','1')
     ->select('companies.name as namacabang',
      'manifests.date_manifest',
      'vehicle_types.name as tipekendaraan',
      'manifests.reff_no',
      'manifests.is_full',
      'manifests.description',
      'routes.name as namatrayek',
      'modas.name as namamoda',
      'manifests.depart',
      'manifests.arrive',
      'containers.container_no',
      'manifests.status',
      'manifests.status_cost',
      'driver.name as namadriver',
      'vehicles.nopol'

    )
    ->orderBy('manifests.id','asc')
    ->get();

    return Excel::create('PackingList FCL/LCL',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Tipe Kendaraan');
        $sheet->SetCellValue('E1','No Reff');
        $sheet->SetCellValue('F1','Full Campuran');
        $sheet->SetCellValue('G1','Keterangan');
        $sheet->SetCellValue('H1','Trayek');
        $sheet->SetCellValue('I1','Moda');
        $sheet->SetCellValue('J1','Waktu Berangkat');
        $sheet->SetCellValue('K1','Waktu Sampai');
        $sheet->SetCellValue('L1','No Container');
        $sheet->SetCellValue('M1','Status');
        $sheet->SetCellValue('N1','Kendaraan');
        $sheet->SetCellValue('O1','Driver');
        $sheet->SetCellValue('P1','Keterangan Biaya');

        $nomer = 1;
        foreach ($data as $i => $value) {
          if($value->is_full==1){
             $full="Full";
          }else{
             $full="Campuran";
          }

          if($value->status==1){
             $status="Terjadwal";
          }else if($value->status==2){
             $status="Berangkat";
          }else if($value->status==3){
             $status="Sampai";
          }else if($value->status==4){
             $status="Selesai";
          }

          if($value->status_cost==1){
            $statusbiaya="Biaya Generate";
          }else{
            $statusbiaya="";
          }


          $urut=$i+2;



          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->namacabang);
          $sheet->SetCellValue('C'.$urut,$value->date_manifest);
          $sheet->SetCellValue('D'.$urut,$value->tipekendaraan);
          $sheet->SetCellValue('E'.$urut,$value->reff_no);
          $sheet->SetCellValue('F'.$urut,$full);
          $sheet->SetCellValue('G'.$urut,$value->description);
          $sheet->SetCellValue('H'.$urut,$value->namatrayek);
          $sheet->SetCellValue('I'.$urut,$value->namamoda);
          $sheet->SetCellValue('J'.$urut,$value->depart);
          $sheet->SetCellValue('K'.$urut,$value->arrive);
          $sheet->SetCellValue('L'.$urut,$value->container_no);
          $sheet->SetCellValue('M'.$urut,$status);
          $sheet->SetCellValue('N'.$urut,$value->nopol);
          $sheet->SetCellValue('O'.$urut,$value->namadriver);
          $sheet->SetCellValue('P'.$urut,$statusbiaya);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function SJ_Drivers_export()
  {
    $data=DB::table('delivery_order_drivers')
    ->leftJoin('manifests','delivery_order_drivers.manifest_id','=','manifests.id')
    ->leftJoin('routes','manifests.route_id','=','routes.id')
    ->leftJoin('vehicles','delivery_order_drivers.vehicle_id','=','vehicles.id')
    ->leftJoin('contacts','delivery_order_drivers.driver_id','=','contacts.id')
    ->leftJoin('contacts as from','delivery_order_drivers.from_id','=','from.id')
    ->leftJoin('contacts as depofrom','delivery_order_drivers.from_address_id','=','depofrom.id')
    ->leftJoin('contacts as to','delivery_order_drivers.to_id','=','to.id')
    ->leftJoin('contacts as depoto','delivery_order_drivers.to_address_id','=','depoto.id')
    ->select('delivery_order_drivers.code',
      'manifests.code as kodepl',
      'delivery_order_drivers.pick_date',
      'vehicles.nopol',
      'contacts.name as namadriver',
      'from.name as namafrom',
      'depofrom.name as alamatfrom',
      'to.name as namato',
      'depoto.name as alamatto',
      'routes.name as namatrayek',
      'delivery_order_drivers.status',
      'delivery_order_drivers.finish_date',
      'delivery_order_drivers.commodity_name'
    )
    ->orderBy('delivery_order_drivers.id','desc')
    ->get();

    return Excel::create('Surat Jalan Driver',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode Job Order');
        $sheet->SetCellValue('C1','Kode Manifest');
        $sheet->SetCellValue('D1','Tanggal');
        $sheet->SetCellValue('E1','Nopol');
        $sheet->SetCellValue('F1','Driver');
        $sheet->SetCellValue('G1','Trayek');
        $sheet->SetCellValue('H1','Dari');
        $sheet->SetCellValue('I1','Dari Alamat');
        $sheet->SetCellValue('J1','Ke');
        $sheet->SetCellValue('K1','Ke Alamat');
        $sheet->SetCellValue('L1','Job Status');
        $sheet->SetCellValue('M1','Jadwal');
        $sheet->SetCellValue('N1','Estimasi Sampai');
        $sheet->SetCellValue('O1','Komoditas');


        $nomer = 1;
        foreach ($data as $i => $value) {


          if($value->status==1){
             $status="Ditugaskan";
          }else{
             $status="Selesai";
          }

          $urut=$i+2;



          $sheet->SetCellValue('A'.$urut,$nomer);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->kodepl);
          $sheet->SetCellValue('D'.$urut,$value->pick_date);
          $sheet->SetCellValue('E'.$urut,$value->nopol);
          $sheet->SetCellValue('F'.$urut,$value->namadriver);
          $sheet->SetCellValue('G'.$urut,$value->namatrayek);
          $sheet->SetCellValue('H'.$urut,$value->namafrom);
          $sheet->SetCellValue('I'.$urut,$value->alamatfrom);
          $sheet->SetCellValue('J'.$urut,$value->namato);
          $sheet->SetCellValue('K'.$urut,$value->alamatto);
          $sheet->SetCellValue('L'.$urut,$status);
          $sheet->SetCellValue('M'.$urut,$value->pick_date);
          $sheet->SetCellValue('N'.$urut,$value->finish_date);
          $sheet->SetCellValue('O'.$urut,$value->commodity_name);
          $nomer++;
        }
      });
    })->download('xls');
  }

  public function bank_export()
  {
    $data=DB::table('banks')
    ->select('code','name'    )
    ->orderBy('id')
    ->get();

    return Excel::create('Semua Bank',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Code');
        $sheet->SetCellValue('C1','Name');
        

        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->name);
        
        }
      });
    })->download('xls');
  }
  public function kapal_export()
  {
    $data=DB::table('vessels')
    ->select('code','name'    )
    ->orderBy('id')
    ->get();

    return Excel::create('Semua Kapal',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Code');
        $sheet->SetCellValue('C1','Name');
        

        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->name);
        
        }
      });
    })->download('xls');
  }
  public function satuan_export()
  {
    $data=DB::table('pieces')
    ->select('name')
    ->orderBy('name')
    ->get();

    return Excel::create('Semua Satuan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Name');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
        
        }
      });
    })->download('xls');
  }
  public function komoditas_export()
  {
    $data=DB::table('commodities')
    ->select('name','is_default','is_expired')
    ->orderBy('name')
    ->get();

    return Excel::create('Semua Komoditas',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Name');
        $sheet->SetCellValue('C1','Expired');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          $sheet->SetCellValue('C'.$urut,$value->is_expired?'Ya':'Tidak');
        
        }
      });
    })->download('xls');
  }
  public function tipe_kontainer_export()
  {
    $data=DB::table('container_types')
    ->select('name','code','size')
    ->orderBy('code')
    ->get();

    return Excel::create('Semua Tipe Kontainer',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Code');
        $sheet->SetCellValue('C1','Name');
        $sheet->SetCellValue('D1','Size');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->name);
          $sheet->SetCellValue('D'.$urut,$value->size);
        
        }
      });
    })->download('xls');
  }
  public function tipe_alamat_export()
  {
    $data=DB::table('address_types')
    ->select('name')
    ->orderBy('name')
    ->get();

    return Excel::create('Semua Tipe Alamat',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Name');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
        
        
        }
      });
    })->download('xls');
  }
  public function kategori_vendor_export()
  {
    $data=DB::table('vendor_types')
    ->select('name')
    ->orderBy('name')
    ->get();

    return Excel::create('Semua Tipe Vendor',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Name');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          
        }
      });
    })->download('xls');
  }
  public function layanan_export()
  {
    $data=DB::table('services')
    ->join('service_types','service_types.id','=','services.service_type_id')
    ->join('accounts','accounts.id','=','services.account_sale_id')
    ->where('is_warehouse', 0)
    ->select('services.name','services.description','service_types.name as service_name','accounts.name as account_name')
    ->orderBy('name')
    ->get();

    return Excel::create('Semua Layanan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Name');
        $sheet->SetCellValue('C1','Keterangan');
        $sheet->SetCellValue('D1','Kelompok');
        $sheet->SetCellValue('E1','Akun Penjualan');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          $sheet->SetCellValue('C'.$urut,$value->description);
          $sheet->SetCellValue('D'.$urut,$value->service_name);
          $sheet->SetCellValue('E'.$urut,$value->account_name);
          
        }
      });
    })->download('xls');
  }
  public function layanan_warehouse_export()
  {
    $data=DB::table('services')
    ->join('service_types','service_types.id','=','services.service_type_id')
    ->join('accounts','accounts.id','=','services.account_sale_id')
    ->where('is_warehouse', 1)
    ->select('services.name','services.description','service_types.name as service_name','accounts.name as account_name')
    ->orderBy('name')
    ->get();

    return Excel::create('Semua Layanan Warehouse',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Name');
        $sheet->SetCellValue('C1','Keterangan');
        $sheet->SetCellValue('D1','Kelompok');
        $sheet->SetCellValue('E1','Akun Penjualan');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          $sheet->SetCellValue('C'.$urut,$value->description);
          $sheet->SetCellValue('D'.$urut,$value->service_name);
          $sheet->SetCellValue('E'.$urut,$value->account_name);
          
        }
      });
    })->download('xls');
  }
  public function customer_stage_export() {
    $data=DB::table('customer_stages')
    ->select('name','bobot','is_close_deal','is_prospect','is_negotiation')
    ->orderBy('name')
    ->get();

    return Excel::create('Semua Customer Stage',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Name');
        $sheet->SetCellValue('C1','Bobot');
        $sheet->SetCellValue('D1','Negosiasi');
        $sheet->SetCellValue('E1','Prospek');
        $sheet->SetCellValue('F1','Close Deal');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          $sheet->SetCellValue('C'.$urut,$value->bobot);
          $sheet->SetCellValue('D'.$urut,$value->is_negotiation?'Ya':'Tidak');
          $sheet->SetCellValue('E'.$urut,$value->is_prospect?'Ya':'Tidak');
          $sheet->SetCellValue('F'.$urut,$value->is_close_deal?'Ya':'Tidak');
          
        }
      });
    })->download('xls');
  }
  public function dermaga_export() {
    $data=DB::table('ports')
    ->select('name','code')
    ->orderBy('name')
    ->get();

    return Excel::create('Semua Dermaga',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Code');
        $sheet->SetCellValue('C1','Name');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->name);
          
          
        }
      });
    })->download('xls');
  }
  public function saldo_akun_export() {
    $data=DB::table('journals')
    ->leftJoin('type_transactions','type_transactions.id','=','journals.type_transaction_id')
    ->where('type_transactions.slug','saldoAwal')
    ->orderBy('name')
    ->select('journals.*')
    ->get();
    
    return Excel::create('Semua Saldo Akun',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Code');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Keterangan');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->date_transaction);
          $sheet->SetCellValue('D'.$urut,$value->description);
          
          
        }
      });
    })->download('xls');
  }
  public function jenis_biaya_export() {
    $data= \App\Model\CostType::with('vendor','company')->orderBy('code','asc')->select('cost_types.*')
    ->get();
    
    return Excel::create('Semua Jenis Biaya',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Nama Biaya');
        $sheet->SetCellValue('D1','Std Vendor');
        $sheet->SetCellValue('E1','Cabang');
        $sheet->SetCellValue('F1','Std Biaya');
        $sheet->SetCellValue('G1','Keterangan');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->name);
          $sheet->SetCellValue('D'.$urut,$value->vendor?$value->vendor->name:'');
          $sheet->SetCellValue('E'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('F'.$urut,$value->initial_cost);
          $sheet->SetCellValue('G'.$urut,$value->description);
          
          
        }
      });
    })->download('xls');
  }
  public function jenis_perawatan_export() {
    $data= \App\Model\VehicleMaintenanceType::query()->get();
    return Excel::create('Semua Jenis Perawatan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $type=[
          1 => 'Time Based (Day)',
          2 => 'KM Based (Kilometer)',
        ];
        $repeat=[
          1 => 'YA',
          0 => 'TIDAK',
        ];
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama');
        $sheet->SetCellValue('C1','Tipe');
        $sheet->SetCellValue('D1','Stt Interval');
        $sheet->SetCellValue('E1','Std Biaya');
        $sheet->SetCellValue('F1','Berkala');
        $sheet->SetCellValue('G1','Keterangan');
        $nomor = 0;
        $admin = "Tidak";
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          $sheet->SetCellValue('C'.$urut,$type[$value->type]);
          $sheet->SetCellValue('D'.$urut,$value->interval);
          $sheet->SetCellValue('E'.$urut,$value->cost);
          $sheet->SetCellValue('F'.$urut,$repeat[$value->is_repeat]);
          $sheet->SetCellValue('G'.$urut,$value->description);
          
          
        }
      });
    })->download('xls');
  }
  public function tipe_kendaraan_export() {
    $data= \App\Model\VehicleType::query()->get();
    return Excel::create('Semua Tipe Kendaraan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama');
        
        $nomor = 0;
        
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
        }
      });
    })->download('xls');
  }
  public function parikan_kendaraan_export() {
    $data= \App\Model\VehicleManufacturer::query()->get();
    return Excel::create('Semua Pabrikan Kendaraan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama');
        
        $nomor = 0;
        
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
        }
      });
    })->download('xls');
  }
  public function kepemilikan_kendaraan_export() {
    $data= \App\Model\VehicleOwner::query()->get();
    return Excel::create('Semua Kepemilikan Kendaraan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama');
        
        $nomor = 0;
        
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
        }
      });
    })->download('xls');
  }
  public function sumbu_posisi_ban_export() {
    $data= \App\Model\VehicleJoint::query()->get();
    return Excel::create('Semua Sumbu dan Posisi Ban',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Konfigurasi Axle');
        $sheet->SetCellValue('C1','Jumlah Ban');
        
        $nomor = 0;
        
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          $sheet->SetCellValue('C'.$urut,$value->tires);
        }
      });
    })->download('xls');
  }
  public function variant_kendaraan_export() {
    $data= \App\Model\VehicleVariant::with('vehicle_joint','vehicle_type','vehicle_manufacturer')->select('vehicle_variants.*')
    ->get();
    return Excel::create('Semua Variant Kendaraan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Model');
        $sheet->SetCellValue('D1','Pabrikan');
        $sheet->SetCellValue('E1','Tipe');
        $sheet->SetCellValue('F1','Tahun');
        $sheet->SetCellValue('G1','Axle');
        
        $nomor = 0;
        
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->name);
          $sheet->SetCellValue('D'.$urut,$value->vehicle_manufacturer?$value->vehicle_manufacturer->name:'');
          $sheet->SetCellValue('E'.$urut,$value->vehicle_type?$value->vehicle_type->name:'');
          $sheet->SetCellValue('F'.$urut,$value->year_manufacture);
          $sheet->SetCellValue('G'.$urut,$value->vehicle_joint?$value->vehicle_joint->name:'');
        }
      });
    })->download('xls');
  }
  public function pengecekan_kendaraan_export() {
    $data= \App\Model\VehicleChecklist::query()->get();
    
    return Excel::create('Semua Pengecekan Kendaraan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama');
        $sheet->SetCellValue('C1','Pengecekan');
        
        
        $nomor = 0;
        
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          $sheet->SetCellValue('C'.$urut,(($value->is_active)?'Ya':'Tidak'));
        }
      });
    })->download('xls');
  }
  public function body_kendaraan_export() {
    $data= \App\Model\VehicleBody::query()->get();
    return Excel::create('Semua Body Kendaraan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama');
        $sheet->SetCellValue('C1','Pengecekan');
        $nomor = 0;
        
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          $sheet->SetCellValue('C'.$urut,$value->is_active?'Ya':'Tidak');
          
        }
      });
    })->download('xls');
  }
  public function tipe_ban_export() {
    $data= \App\Model\TireType::all();
    return Excel::create('Semua Tipe Ban',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama');
        $nomor = 0;
        
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          
        }
      });
    })->download('xls');
  }
  public function ukuran_ban_export() {
    $data= \App\Model\TireSize::all();
    return Excel::create('Semua Ukuran Ban',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Nama');
        $nomor = 0;
        
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->name);
          
        }
      });
    })->download('xls');
  }
  public function invoice_jual_export() {
    $data= \App\Model\Invoice::with('customer','company')->select('invoices.*',DB::raw("(grand_total+grand_total_additional) as total"))->get();
    return Excel::create('Invoice Jual',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Kode Invoice');
        $sheet->SetCellValue('D1','Tanggal');
        $sheet->SetCellValue('E1','Customer');
        $sheet->SetCellValue('F1','Total');
        $sheet->SetCellValue('G1','Status');

        $nomor = 0;
        $stt=[
          1=>'Diajukan',
          2=>'Disetujui',
          3=>'Invoice',
          4=>'Terbayar Sebagian',
          5=>'Lunas',
        ];
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->created_at)));
          $sheet->SetCellValue('E'.$urut,$value->customer?$value->customer->name:'');
          $sheet->SetCellValue('F'.$urut,$value->grand_total);
          $sheet->SetCellValue('G'.$urut,$stt[$value->status]);
          
        }
      });
    })->download('xls');
  }
  public function invoice_vendor_export(Request $request) {
      $wr = '1=1';
      
    if($request->start_date)
        $wr .= " AND invoice_vendors.date_manifest >= '". dateDB($request->start_date) ."'";
    
    if($request->end_date)
        $wr .= " AND invoice_vendors.date_manifest <= '". dateDB($request->end_date) ."'";

    if($request->company_id)
        $wr .= " AND invoice_vendors.company_id = {$request->company_id}";
    
    if($request->vendor_id)
        $wr .= " AND invoice_vendors.vendor_id = {$request->vendor_id}";
    
    if($request->status)
        $wr .= " AND invoice_vendors.status = {$request->status}";
    
    $item= \App\Model\InvoiceVendor::with('vendor','company')
        ->select('invoice_vendors.*')
        ->whereRaw($wr)
        ->get();
    
    return Excel::create('Invoice Jual',function($excel) use ($item){
      $excel->sheet('data', function($sheet) use ($item){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Kode Invoice');
        $sheet->SetCellValue('D1','Supplier / Vendor');
        $sheet->SetCellValue('E1','Tanggal Tagihan');
        $sheet->SetCellValue('F1','Tanggal Diterima');
        $sheet->SetCellValue('G1','Total');
        $sheet->SetCellValue('H1','Status');

        $nomor = 0;
        $stt=[
          1=>'Belum Lunas',
          2=>'Lunas',
        ];
        foreach ($item as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->vendor?$value->vendor->name:'');
          $sheet->SetCellValue('E'.$urut,date('d-m-Y',strtotime($value->date_invoice)));
          $sheet->SetCellValue('F'.$urut,date('d-m-Y',strtotime($value->date_receive)));
          $sheet->SetCellValue('G'.$urut,$value->grand_total);
          $sheet->SetCellValue('H'.$urut,$stt[$value->status]);
          
        }
      });
    })->download('xls');
  }
  public function progress_operasional_export(Request $request) {
    $wr="kpi_logs.id in (select max(kpi_logs.id) from kpi_logs group by kpi_logs.job_order_id)";
    $wr_jo="1=1";
    $params=$request->params;
    if (($params['customer_id']??false)) {
      $wr_jo.=" AND customer_id = ".$params['customer_id'];
    }
    if (($params['job_order']??false)) {
      $txt=$params['job_order'];
      $wr_jo.=" AND code LIKE '%$txt%'";
    }
    if (($params['create_by']??false)) {
      $wr.=" AND create_by = ".$params['create_by'];
    }
    if (($params['service']??false)) {
      $wr_jo.=" AND service_id = ".$params['service'];
    }
    if (($params['start_date']??false) && ($params['end_date']??false)) {
      $start=Carbon::parse($params['start_date'])->format('Y-m-d H:i:s');
      $end=Carbon::parse($params['end_date'])->format('Y-m-d H:i:s');
      $wr.=" AND date(date_update) between '$start' AND '$end'";
    }
    if (auth()->user()->is_admin==0) {
      $wr_jo.=" AND company_id = ".auth()->user()->company_id;
    }
    $data = KpiLog::with('job_order','job_order.service','job_order.customer','creates','kpi_status')
        ->whereHas('job_order', function($query) use ($wr_jo){
        $query->whereRaw($wr_jo);
        })
        ->leftJoin('job_orders','job_orders.id','kpi_logs.job_order_id')
        ->whereRaw($wr)
        ->selectRaw('kpi_logs.*')->get();
    return Excel::create('Progress Operasional',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Tanggal');
        $sheet->SetCellValue('C1','Layanan');
        $sheet->SetCellValue('D1','Job Order');
        $sheet->SetCellValue('E1','Customer');
        $sheet->SetCellValue('F1','Update');
        $sheet->SetCellValue('G1','Keterangan');
        $sheet->SetCellValue('H1','Status');

        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,date('d-m-Y',strtotime($value->date_update)));
          $sheet->SetCellValue('C'.$urut,$value->job_order->service->name);
          $sheet->SetCellValue('D'.$urut,$value->job_order->code);
          $sheet->SetCellValue('E'.$urut,$value->job_order->customer->name);
          $sheet->SetCellValue('F'.$urut,$value->creates->name);
          $sheet->SetCellValue('G'.$urut,$value->description);
          $sheet->SetCellValue('H'.$urut,$value->kpi_status->name);
          
        }
      });
    })->download('xls');
  }
  public function daftar_gudang_export() {
    $data= \App\Model\Warehouse::with('company')->select('warehouses.*')->get();
    return Excel::create('Daftar Gudang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Nama');
        $sheet->SetCellValue('E1','Alamat');
        $sheet->SetCellValue('F1','Kapasitas Volume');
        $sheet->SetCellValue('G1','Kapasitas Tonase');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->name);
          $sheet->SetCellValue('E'.$urut,$value->address);
          $sheet->SetCellValue('F'.$urut,$value->capacity_volume);
          $sheet->SetCellValue('G'.$urut,$value->capacity_tonase);
          
          
        }
      });
    })->download('xls');
  }
  public function daftar_rak_export() {
    $data= \App\Model\Rack::with('warehouse.company')->select('racks.*')->get();
    return Excel::create('Daftar Rak',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Gudang');
        $sheet->SetCellValue('D1','Kode');
        $sheet->SetCellValue('E1','Kapasitas Volume');
        $sheet->SetCellValue('F1','Kapasitas Kg');

        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->warehouse?($value->warehouse->company?$value->warehouse->company->name:''):'');
          $sheet->SetCellValue('C'.$urut,$value->warehouse?$value->warehouse->name:'');
          $sheet->SetCellValue('D'.$urut,$value->code);
          $sheet->SetCellValue('E'.$urut,$value->capacity_volume);
          $sheet->SetCellValue('F'.$urut,$value->capacity_tonase);
          
        }
      });
    })->download('xls');
  }
  public function penerimaan_barang_export() {
    $data= \App\Model\WarehouseReceipt::with('customer.company','warehouse','sender','receiver','staff')
    ->leftJoin(DB::raw("(select sum(qty) as total,header_id from warehouse_receipt_details group by header_id) det"),'det.header_id','=','warehouse_receipts.id')
    ->select('warehouse_receipts.*','det.*')->get();
    return Excel::create('Penerimaan Barang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Gudang');
        $sheet->SetCellValue('D1','Customer');
        $sheet->SetCellValue('E1','Tanggal Terima');
        $sheet->SetCellValue('F1','Selesai Stripping');
        $sheet->SetCellValue('G1','Shipper');
        $sheet->SetCellValue('H1','Consignee');
        $sheet->SetCellValue('I1','Staff Gudang');
        $sheet->SetCellValue('J1','Tujuan');
        $sheet->SetCellValue('K1','Total Barang');

        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->warehouse?($value->warehouse->company?$value->warehouse->company->name:''):'');
          $sheet->SetCellValue('C'.$urut,$value->warehouse?$value->warehouse->name:'');
          $sheet->SetCellValue('D'.$urut,$value->customer?$value->customer->name:'');
          $sheet->SetCellValue('E'.$urut,date('Y-m-d',strtotime($value->receive_date)));
          $sheet->SetCellValue('F'.$urut,date('Y-m-d',strtotime($value->receive_date)));
          $sheet->SetCellValue('G'.$urut,$value->sender?$value->sender->name:'');
          $sheet->SetCellValue('H'.$urut,$value->receiver?$value->receiver->name:'');
          $sheet->SetCellValue('I'.$urut,$value->staff?$value->staff->name:'');
          $sheet->SetCellValue('J'.$urut,$value->city_to);
          $sheet->SetCellValue('K'.$urut,$value->total);
          
        }
      });
    })->download('xls');
  }
  public function semua_kendaraan_export(Request $request) {
    $wr = "1=1 and vehicles.is_internal = 0";

    if($request->company_id)
        $wr .= " AND vehicles.company_id = {$request->company_id}";

    $data = \App\Model\Vehicle::with('company','company.area','vehicle_variant','vehicle_variant.vehicle_type','supplier')
        ->whereRaw($wr)
        ->select('vehicles.*')->get();
    
    return Excel::create('Semua Kendaraan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode Lambung');
        $sheet->SetCellValue('C1','Nopol');
        $sheet->SetCellValue('D1','Model');
        $sheet->SetCellValue('E1','Tipe');
        $sheet->SetCellValue('F1','Pemilik');
        $sheet->SetCellValue('G1','Area');
        $sheet->SetCellValue('H1','Cabang');
        $sheet->SetCellValue('I1','KM Akhir');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->nopol);
          $sheet->SetCellValue('D'.$urut,$value->vehicle_variant?$value->vehicle_variant->name:'');
          $sheet->SetCellValue('E'.$urut,$value->vehicle_variant?($value->vehicle_variant->vehicle_type?$value->vehicle_variant->vehicle_type->name:''):'');
          $sheet->SetCellValue('F'.$urut,$value->supplier?$value->supplier->name:'');
          $sheet->SetCellValue('G'.$urut,$value->company?($value->company->area?$value->company->area->name:''):'');
          $sheet->SetCellValue('H'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('I'.$urut,$value->last_km);
  
        }
      });
    })->download('xls');
  }
  
  public function kilometer_kendaraan_export(Request $request)
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

    $data= \App\Model\VehicleDistance::with('vehicle')
        ->leftJoin('vehicles', 'vehicles.id', 'vehicle_distances.vehicle_id')
        ->whereRaw($wr)
        ->select('vehicle_distances.*')->get();

    return Excel::create('Kilometer Kendaraan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Nopol');
        $sheet->SetCellValue('D1','Tanggal Update');
        $sheet->SetCellValue('E1','Kilometer');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->vehicle?$value->vehicle->code:'');
          $sheet->SetCellValue('C'.$urut,$value->vehicle?$value->vehicle->nopol:'');
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_distance)));
          $sheet->SetCellValue('E'.$urut,$value->distance);
  
        }
      });
    })->download('xls');
  }
  public function kendaraan_pengecekan_export(Request $request) {
    $wr = '1=1';

    if($request->company_id)
      $wr .= " AND vehicle_checklist_items.company_id = {$request->company_id}";
    
    if($request->vehicle_id)
      $wr .= " AND vehicle_checklist_items.vehicle_id = {$request->vehicle_id}";
    
    if($request->start_date)
      $wr .= ' AND vehicle_checklist_items.date_transaction >= "'. dateDB($request->start_date) .'"';

    if($request->end_date)
      $wr .= ' AND vehicle_checklist_items.date_transaction <= "'. dateDB($request->end_date) .'"';
    
    $data = VehicleChecklistItem::with('vehicle','company')
        ->whereRaw($wr)
        ->select('vehicle_checklist_items.*')->get();

    return Excel::create('Pengecekan Kendaraan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Kendaraan');
        $sheet->SetCellValue('D1','Waktu Pengecekan');
        $sheet->SetCellValue('E1','Petugas');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->vehicle?$value->vehicle->nopol:'');
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('E'.$urut,$value->officer);
  
        }
      });
    })->download('xls');
  }
  public function register_vendor_export() {
    $data= \App\Model\Contact::leftJoin('cities','cities.id','=','contacts.city_id')
    ->where('is_vendor', 1)
    ->where('vendor_status_approve', 1)
    ->select('contacts.*',DB::raw("CONCAT(cities.type,' ',cities.name) as cityname"),DB::raw("IFNULL(contacts.code,'-') as codes"))->get();
    return Excel::create('Register Vendor',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Nama');
        $sheet->SetCellValue('D1','Alamat');
        $sheet->SetCellValue('E1','Telephone');
        $sheet->SetCellValue('F1','Email');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->codes);
          $sheet->SetCellValue('C'.$urut,$value->name);
          $sheet->SetCellValue('D'.$urut,$value->address);
          $sheet->SetCellValue('E'.$urut,$value->phone);
          $sheet->SetCellValue('F'.$urut,$value->email);
  
        }
      });
    })->download('xls');
  }
  public function inventory_warehouse_export() {
    $data= \App\Model\Warehouse::with('company')->select('warehouses.*')->get();
    return Excel::create('Inventory Warehouse',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Nama');
        $sheet->SetCellValue('E1','Alamat');
        $sheet->SetCellValue('F1','Email');
        $sheet->SetCellValue('G1','Kapasitas Volume');
        $sheet->SetCellValue('H1','Kapasitas Tonase');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->name);
          $sheet->SetCellValue('E'.$urut,$value->address);
          $sheet->SetCellValue('F'.$urut,$value->email);
          $sheet->SetCellValue('G'.$urut,$value->capacity_volume.' m3');
          $sheet->SetCellValue('H'.$urut,$value->capacity_tonase.' Kg');
        }
      });
    })->download('xls');
  }
  public function inventory_kategori_export() {
    $data= \App\Model\Category::leftJoin('categories as parent','parent.id','=','categories.parent_id')->orderByRaw("COALESCE(categories.parent_id,categories.id), categories.parent_id IS NOT NULL, categories.id")->select('categories.*','parent.name as pname')->get();
    return Excel::create('Inventory Kategori',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kategori Induk');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Nama');
        $sheet->SetCellValue('E1','Aset');
        $sheet->SetCellValue('F1','Jasa');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->pname?$value->pname:'-');
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->name);
          $sheet->SetCellValue('E'.$urut,$value->is_asset?'Ya':'Tidak');
          $sheet->SetCellValue('F'.$urut,$value->is_jasa?'Ya':'Tidak');
        }
      });
    })->download('xls');
  }
  public function inventory_item_export() {
    $data= \App\Model\Item::with('category','category.parent')->select('items.*')->get();
    return Excel::create('Inventory Item',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Nama Item');
        $sheet->SetCellValue('D1','Part Number');
        $sheet->SetCellValue('E1','Kategori');
        $sheet->SetCellValue('F1','Sub Kategori');
        $sheet->SetCellValue('G1','Std Harga');
        $sheet->SetCellValue('H1','Keterangan');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->name);
          $sheet->SetCellValue('D'.$urut,$value->part_number);
          $sheet->SetCellValue('E'.$urut,$value->category?($value->category->parent?$value->category->parent->name:''):'');
          $sheet->SetCellValue('F'.$urut,$value->category?$value->category->name:'');
          $sheet->SetCellValue('G'.$urut,$value->initial_cost);
          $sheet->SetCellValue('H'.$urut,$value->description);
       }
      });
    })->download('xls');
  }
  public function persediaan_awal_export() {
    $data= \App\Model\StockInitial::with('warehouse','company','item','item.category')->select('stock_initials.*')->get();
    return Excel::create('Persediaan Awal',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Gudang');
        $sheet->SetCellValue('D1','Tanggal');
        $sheet->SetCellValue('E1','Kode Transaksi');
        $sheet->SetCellValue('F1','Nama Item');
        $sheet->SetCellValue('G1','Sub Kategory');
        $sheet->SetCellValue('H1','Qty');
        $sheet->SetCellValue('I1','Harga Per Item');
        $sheet->SetCellValue('J1','Total');
        $sheet->SetCellValue('K1','Keterangan');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->warehouse?$value->warehouse->name:'');
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('E'.$urut,$value->code);
          $sheet->SetCellValue('F'.$urut,$value->item?$value->item->name:'');
          $sheet->SetCellValue('G'.$urut,$value->category?$value->category->name:'');
          $sheet->SetCellValue('H'.$urut,$value->qty);
          $sheet->SetCellValue('I'.$urut,$value->price);
          $sheet->SetCellValue('J'.$urut,$value->total);
          $sheet->SetCellValue('K'.$urut,$value->description);
       }
      });
    })->download('xls');
  }
  public function permintaan_pembelian_export() {
    $data= \App\Model\PurchaseRequest::with('company','supplier')->select('purchase_requests.*')->get();
    return Excel::create('Permintaan Pembelian',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Kode Transaksi');
        $sheet->SetCellValue('D1','Tanggal Permintaan');
        $sheet->SetCellValue('E1','Tanggal Kebutuhan');
        $sheet->SetCellValue('F1','Supplier');
        $sheet->SetCellValue('G1','Status');
        
        $stt=[
          0 => 'Ditolak',
          1 => 'Belum Persetujuan',
          2 => 'Sudah Persetujuan',
          3 => 'Purchase Order',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_request)));
          $sheet->SetCellValue('E'.$urut,date('d-m-Y',strtotime($value->date_needed)));
          $sheet->SetCellValue('F'.$urut,$value->supplier?$value->supplier->name:'');
          $sheet->SetCellValue('G'.$urut,$stt[$value->status]);
         
       }
      });
    })->download('xls');
  }
  public function pembelian_export() {
    $data= \App\Model\PurchaseOrder::with('company','supplier')->select('purchase_orders.*')->get();
    return Excel::create('Pembelian ',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Kode Transaksi');
        $sheet->SetCellValue('D1','Tanggal Permintaan');
        $sheet->SetCellValue('E1','Supplier');
        $sheet->SetCellValue('F1','Status');
        
        $stt=[
          1 => 'Purchase Order',
          2 => 'Sudah Diterima',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->po_date)));
          $sheet->SetCellValue('E'.$urut,$value->supplier?$value->supplier->name:'');
          $sheet->SetCellValue('F'.$urut,$stt[$value->po_status]);
         
       }
      });
    })->download('xls');
  }
  public function inventory_penerimaan_barang_export() {
    $data= \App\Model\Receipt::with('company','purchase_order','lists','lists.warehouse')->select('receipts.*')->get();
    return Excel::create('Inventory Penerimaan Barang ',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Kode PO');
        $sheet->SetCellValue('D1','Kode Penerimaan');
        $sheet->SetCellValue('E1','Tanggal Terima');
        $sheet->SetCellValue('F1','Gudang');
        $sheet->SetCellValue('G1','Status');
        
        $stt=[
          1 => 'Belum Lengkap',
          2 => 'Sudah Lengkap',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('E'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->po_date)));
          $sheet->SetCellValue('F'.$urut,$value->lists->map(function($list){
            return '- '.$list->warehouse->name;
          })->implode('<br>'));
          $sheet->SetCellValue('G'.$urut,$stt[$value->status]);
       }
      });
    })->download('xls');
  }

  public function penggunaan_barang_export(Request $request)
  {
    $item = UsingItem::with('company','vehicle')
        ->select('using_items.*');

    $start_date = $request->start_date;
    $start_date = $start_date != null ? new DateTime($start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? new DateTime($end_date) : '';
    $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_request', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

    $start_date_penggunaan = $request->start_date_penggunaan;
    $start_date_penggunaan = $start_date_penggunaan != null ? new DateTime($start_date_penggunaan) : '';
    $end_date_penggunaan = $request->end_date_penggunaan;
    $end_date_penggunaan = $end_date_penggunaan != null ? new DateTime($end_date_penggunaan) : '';
    $item = $start_date_penggunaan != '' && $end_date_penggunaan != '' ? $item->whereBetween('date_pemakaian', [$start_date_penggunaan->format('Y-m-d'), $end_date_penggunaan->format('Y-m-d')]) : $item;

    $company_id = $request->company_id;
    $company_id = $company_id != null ? $company_id : '';
    $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;

    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status', $status) : $item;

    $data= $item->get();
    
    return Excel::create('Penggunaan Barang ',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Kendaraan');
        $sheet->SetCellValue('E1','Tanggal Pengajuan');
        $sheet->SetCellValue('F1','Tanggal Penggunaan');
        $sheet->SetCellValue('G1','Status');
        
        $stt=[
          1 => 'Pengajuan',
          2 => 'Disetujui',
          3 => 'Proses Penggunaan',
          4 => 'Selesai',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->vehicle?$value->vehicle->code:'');
          $sheet->SetCellValue('E'.$urut,date('d-m-Y',strtotime($value->date_request)));
          $sheet->SetCellValue('F'.$urut,date('d-m-Y',strtotime($value->date_pemakaian)));
          $sheet->SetCellValue('G'.$urut,$stt[$value->status]);
       }
      });
    })->download('xls');
  }
  public function stok_gudang_export(Request $request) {
    $item =  \App\Model\WarehouseStock::with('warehouse.company','warehouse','item','item.category');

    $warehouse_id = $request->warehouse_id;
    $warehouse_id = $warehouse_id != null ? $warehouse_id : '';
    $item = $warehouse_id != '' ? $item->where('warehouse_id', $warehouse_id) : $item;

    $start_qty = $request->start_qty;
    $start_qty = $start_qty != null ? $start_qty : 0;
    $end_qty = $request->end_qty;
    $end_qty = $end_qty != null ? $end_qty : 0;
    $item = $end_qty != 0 ? $item->whereBetween('qty', [$start_qty, $end_qty]) : $item;

    $item = $item->select('warehouse_stocks.*')->get();
    return Excel::create('Stok Gudang ',function($excel) use ($item){
      $excel->sheet('data', function($sheet) use ($item){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Gudang');
        $sheet->SetCellValue('D1','Item');
        $sheet->SetCellValue('E1','Kategori');
        $sheet->SetCellValue('F1','Stok Akhir');
        
        $nomor = 0;
        foreach ($item as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->warehouse?($value->warehouse->company?$value->warehouse->company->name:''):'');
          $sheet->SetCellValue('C'.$urut,$value->warehouse?$value->warehouse->name:'');
          $sheet->SetCellValue('D'.$urut,$value->item?$value->item->name:'');
          $sheet->SetCellValue('E'.$urut,$value->item?($value->item->category?$value->item->category->name:''):'');
          $sheet->SetCellValue('F'.$urut,$value->qty);
       }
      });
    })->download('xls');
  }
  public function penyesuaian_barang_export() {
    $data= \App\Model\StockAdjustment::with('company','warehouse','creates')->select('stock_adjustments.*')->get();
    return Excel::create('Penyesuaian Barang ',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Wilayah');
        $sheet->SetCellValue('C1','Gudang');
        $sheet->SetCellValue('D1','Kode Transaksi');
        $sheet->SetCellValue('E1','Tanggal Penyesuaian');
        $sheet->SetCellValue('F1','Pelaksana');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company?$value->company->name:'');
          $sheet->SetCellValue('C'.$urut,$value->warehouse?$value->warehouse->name:'');
          $sheet->SetCellValue('D'.$urut,$value->code);
          $sheet->SetCellValue('E'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('F'.$urut,$value->creates?$value->creates->name:'');
       }
      });
    })->download('xls');
  }
  public function asset_group_export() {
    $data= \App\Model\AssetGroup::query()->get();
    return Excel::create('Asset Group Export ',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Nama Kelompok');
        $sheet->SetCellValue('D1','Mode Depresiasi');
        $sheet->SetCellValue('E1','Keterangan');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->name);
          $sheet->SetCellValue('D'.$urut,$value->method);
          $sheet->SetCellValue('E'.$urut,$value->description);
       }
      });
    })->download('xls');
  }
  public function saldo_awal_asset_export() {
    $data= \App\Model\Asset::with('company','asset_group')->select('assets.*');
    return Excel::create('Saldo Awal Export ',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Nama');
        $sheet->SetCellValue('E1','Nama Kelompok');
        $sheet->SetCellValue('F1','Tipe Asset');
        $sheet->SetCellValue('G1','Mode Depresiasi');
        $sheet->SetCellValue('H1','Status');
        
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company->name);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->name);
          $sheet->SetCellValue('E'.$urut,$value->asset_group->name);
          $sheet->SetCellValue('F'.$urut,$value->asset_type);
          $sheet->SetCellValue('G'.$urut,$value->method);
          $sheet->SetCellValue('H'.$urut,$value->status);
       }
      });
    })->download('xls');
  }
  public function um_supplier_export(Request $request)
  {
    $data = FinanceApiController::um_supplier_query($request)->get();
    return Excel::create('Deposit Supplier ',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Code');
        $sheet->SetCellValue('C1','Tanggal Transaksi');
        $sheet->SetCellValue('D1','Supplier');
        $sheet->SetCellValue('E1','Cabang');
        $sheet->SetCellValue('F1','DP/Lebih Bayar');
        $sheet->SetCellValue('G1','DP Terpakai');
        $sheet->SetCellValue('H1','Sisa');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('D'.$urut,$value->contact->name);
          $sheet->SetCellValue('E'.$urut,$value->company->name);
          $sheet->SetCellValue('F'.$urut,$value->debet);
          $sheet->SetCellValue('G'.$urut,$value->credit);
          $sheet->SetCellValue('H'.$urut,$value->sisa);
       }
      });
    })->download('xls');
  }

  public function um_customer_export(Request $request)
  {
    $data = FinanceApiController::um_customer_query($request)->get();
    return Excel::create('Deposit Customer ',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Code');
        $sheet->SetCellValue('C1','Tanggal Transaksi');
        $sheet->SetCellValue('D1','Customer');
        $sheet->SetCellValue('E1','Cabang');
        $sheet->SetCellValue('F1','DP/Lebih Bayar');
        $sheet->SetCellValue('G1','DP Terpakai');
        $sheet->SetCellValue('H1','Sisa');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('D'.$urut,$value->contact->name);
          $sheet->SetCellValue('E'.$urut,$value->company->name);
          $sheet->SetCellValue('F'.$urut,$value->debet);
          $sheet->SetCellValue('G'.$urut,$value->credit);
          $sheet->SetCellValue('H'.$urut,$value->sisa);
       }
      });
    })->download('xls');
  }

  public function draf_pelunasan_hutang_export(Request $request)
  {
    $data= FinanceApiController::debt_query($request)->get();
    return Excel::create('Draf Pelunasan Hutang ',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Cabang');
        $sheet->SetCellValue('E1','Keterangan');
        $sheet->SetCellValue('F1','Status');
        $stt=[
          1 => 'BELUM TERBAYAR',
          2 => 'TERBAYAR',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_request)));
          $sheet->SetCellValue('D'.$urut,$value->company->name);
          $sheet->SetCellValue('E'.$urut,$value->description);
          $sheet->SetCellValue('F'.$urut,$stt[$value->status]);
       }
      });
    })->download('xls');
  }
  public function pelunasan_hutang_export(Request $request) {
    $data= FinanceApiController::debt_payment_query($request)->get();
    return Excel::create('Pelunasan Hutang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Tanggal Penagihan');
        $sheet->SetCellValue('D1','Tanggal Pembayaran');
        $sheet->SetCellValue('E1','Cabang');
        $sheet->SetCellValue('F1','Status');
        $stt=[
          1 => 'BELUM TERBAYAR',
          2 => 'TERBAYAR',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_request)));
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_receive)));
          $sheet->SetCellValue('E'.$urut,$value->company->name);
          $sheet->SetCellValue('F'.$urut,$stt[$value->status]);
       }
      });
    })->download('xls');
  }
  public function draf_penagihan_hutang_export(Request $request) {
    $data= FinanceApiController::bill_query($request)->get();
    return Excel::create('Draf Penagihan Hutang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Cabang');
        $sheet->SetCellValue('D1','Tanggal');
        $sheet->SetCellValue('E1','Customer');
        $sheet->SetCellValue('F1','Jumlah Ditagihkan');
        $sheet->SetCellValue('G1','Status');
        $stt=[
          1 => 'BELUM TERBAYAR',
          2 => 'TERBAYAR',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->company->name);
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_request)));
          $sheet->SetCellValue('E'.$urut,$value->customer->name);
          $sheet->SetCellValue('F'.$urut,$value->total);
          $sheet->SetCellValue('G'.$urut,$stt[$value->status]);
       }
      });
    })->download('xls');
  }
  public function penagihan_hutang_export() {
    $data= \App\Model\Bill::with('company','customer')->where('status', 2)->select('bills.*')->get();
    return Excel::create('Penagihan Hutang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Tanggal Penagihan');
        $sheet->SetCellValue('D1','Tanggal Pembayaran');
        $sheet->SetCellValue('E1','Customer');
        $sheet->SetCellValue('F1','Cabang');
        $sheet->SetCellValue('G1','Status');
        $stt=[
          1 => 'BELUM TERBAYAR',
          2 => 'TERBAYAR',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_request)));
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_receive)));
          $sheet->SetCellValue('E'.$urut,$value->customer->name);
          $sheet->SetCellValue('F'.$urut,$value->company->name);
          $sheet->SetCellValue('G'.$urut,$stt[$value->status]);
       }
      });
    })->download('xls');
  }
  public function nota_potong_penjualan_export() {
    $data= \App\Model\NotaCredit::with('company','contact')->select('nota_credits.*')->get();
    return Excel::create('Nota Potong Penjualan',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Tanggal');
        $sheet->SetCellValue('E1','Supplier');
        $sheet->SetCellValue('F1','Jumlah');
        $stt=[
          1 => 'BELUM TERBAYAR',
          2 => 'TERBAYAR',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company->name);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('E'.$urut,$value->contract->name);
          $sheet->SetCellValue('F'.$urut,$value->amount);
       }
      });
    })->download('xls');
  }
  public function nota_potong_pembelian_export() {
    $data= \App\Model\NotaDebet::with('company','contact')->select('nota_debets.*')->get();
    return Excel::create('Nota Potong Pembelian',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','Tanggal');
        $sheet->SetCellValue('E1','Supplier');
        $sheet->SetCellValue('F1','Jumlah');
        $stt=[
          1 => 'BELUM TERBAYAR',
          2 => 'TERBAYAR',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company->name);
          $sheet->SetCellValue('C'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('E'.$urut,$value->contract->name);
          $sheet->SetCellValue('F'.$urut,$value->amount);
       }
      });
    })->download('xls');
  }

  public function cek_giro_export(Request $request) {
    $data= FinanceApiController::cek_giro_query($request)->get();
    return Excel::create('Cek Giro',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Kode');
        $sheet->SetCellValue('D1','No Cek / Giro');
        $sheet->SetCellValue('E1','Tanggal Terbit');
        $sheet->SetCellValue('F1','Tanggal Efektif');
        $sheet->SetCellValue('G1','Penerbit');
        $sheet->SetCellValue('H1','Penerima');
        $sheet->SetCellValue('I1','Jumlah');
        $sheet->SetCellValue('J1','Tipe');
        $sheet->SetCellValue('K1','Kliring');
        $sheet->SetCellValue('L1','Kosong');
        $sheet->SetCellValue('M1','No Ref');
 
        $type=[
          1=>'Cheque',
          2=>'Giro',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,$value->company->name);
          $sheet->SetCellValue('D'.$urut,$value->giro_no);
          $sheet->SetCellValue('E'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('F'.$urut,date('d-m-Y',strtotime($value->date_effective)));
          $sheet->SetCellValue('G'.$urut,$value->penerbit->name);
          $sheet->SetCellValue('H'.$urut,(!is_null($value->penerima))?$value->penerima->name:"");
          $sheet->SetCellValue('I'.$urut,$value->amount);
          $sheet->SetCellValue('J'.$urut,$type[$value->type]);
          $sheet->SetCellValue('L'.$urut,$value->is_kliring?'Ya':'Tidak');
          $sheet->SetCellValue('M'.$urut,$value->is_empty?'Ya':'Tidak');
          $sheet->SetCellValue('N'.$urut,$value->reff_no);
       }
      });
    })->download('xls');
  }
  public function permintaan_mutasi_export(Request $request){
    $data = FinanceApiController::cash_migration_query($request)->get();

    return Excel::create('Permintaan Mutasi',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Cabang Asal');
        $sheet->SetCellValue('E1','Cabang Tujuan');
        $sheet->SetCellValue('F1','Kas/Bank Asal');
        $sheet->SetCellValue('G1','Kas/Bank Tujuan');
        $sheet->SetCellValue('H1','Total');
        $sheet->SetCellValue('I1','Status');
        
        $stt=[
          1 => 'Pengajuan',
          2 => 'Disetujui Keuangan',
          3 => 'Disetujui Direksi',
          4 => 'Realisasi',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_request)));
          $sheet->SetCellValue('D'.$urut,$value->company_from);
          $sheet->SetCellValue('E'.$urut,$value->company_to);
          $sheet->SetCellValue('F'.$urut,$value->account_from);
          $sheet->SetCellValue('G'.$urut,$value->account_to);
          $sheet->SetCellValue('H'.$urut,$value->total);
          $sheet->SetCellValue('I'.$urut,$stt[$value->status]);
       }
      });
    })->download('xls');
  }

  public function realisasi_mutasi_export(Request $request)
  {
    $data = FinanceApiController::cash_migration_query($request)->get();
    return Excel::create('Realisasi Mutasi',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Cabang Asal');
        $sheet->SetCellValue('E1','Cabang Tujuan');
        $sheet->SetCellValue('F1','Kas/Bank Asal');
        $sheet->SetCellValue('G1','Kas/Bank Tujuan');
        $sheet->SetCellValue('H1','Total');
        $sheet->SetCellValue('I1','Status');
        
        $stt=[
          1 => 'Pengajuan',
          2 => 'Disetujui Keuangan',
          3 => 'Disetujui Direksi',
          4 => 'Realisasi',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;

          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_request)));
          $sheet->SetCellValue('D'.$urut,$value->company_from);
          $sheet->SetCellValue('E'.$urut,$value->company_to);
          $sheet->SetCellValue('F'.$urut,$value->account_from);
          $sheet->SetCellValue('G'.$urut,$value->account_to);
          $sheet->SetCellValue('H'.$urut,$value->total);
          $sheet->SetCellValue('I'.$urut,$stt[$value->status]);
       }
      });
    })->download('xls');
  }
  public function transaksi_kas_bank_export(Request $request){
    $data = FinanceApiController::cash_transaction_query($request)->get();
    return Excel::create('Transaksi Kas / Bank',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Cabang');
        $sheet->SetCellValue('D1','Tanggal');
        $sheet->SetCellValue('E1','Tipe');
        $sheet->SetCellValue('F1','Total');
        $sheet->SetCellValue('G1','Keterangan');
        $sheet->SetCellValue('H1','Status');
        $sheet->SetCellValue('I1','Status Biaya');
        $stt = [
          1 => 'Printed',
          2 => 'Edited',
          3 => 'Deleted'
        ];
        $stt_cost = [
          1 => 'Belum Persetujuan',
          2 => 'Disetujui',
          3 => 'Selesai',
          4 => 'Ditolak',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
         
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('D'.$urut,$value->company->name);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('E'.$urut,$value->type_transaction->name);
          $sheet->SetCellValue('F'.$urut,$value->total);
          $sheet->SetCellValue('G'.$urut,$value->description);
          $sheet->SetCellValue('H'.$urut,$stt[$value->status]);
          $sheet->SetCellValue('I'.$urut,$stt_cost[$value->status_cost]);
       }
      });
    })->download('xls');
  }

  public function pengajuan_biaya_export(Request $request)
  {
    $data = FinanceApiController::submission_cost_query($request)->get();

    return Excel::create('Pengajuan Biaya',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Tanggal Pengajuan');
        $sheet->SetCellValue('D1','Tanggal Biaya');
        $sheet->SetCellValue('E1','Jenis');
        $sheet->SetCellValue('F1','Kode');
        $sheet->SetCellValue('G1','Uraian');
        $sheet->SetCellValue('H1','Biaya');
        $sheet->SetCellValue('I1','Status');
        $stt=[
          1 => 'Diajukan',
          2 => 'Disetujui',
          3 => 'Ditolak',
          4 => 'Diposting',
          5 => 'Revisi',
        ];
        $type=[
          1 => 'JOB ORDER',
          2 => 'PACKING LIST',
          3 => 'PICKUP ORDER',
          4 => 'TRANSAKSI KAS',
          5 => 'KAS BON',
        ];
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->cname);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->created_at)));
          $sheet->SetCellValue('D'.$urut,date('d-m-Y',strtotime($value->date_submission)));
          $sheet->SetCellValue('E'.$urut,$type[$value->type_submission]);
          $sheet->SetCellValue('F'.$urut,$value->codes);
          $sheet->SetCellValue('G'.$urut,$value->description);
          $sheet->SetCellValue('H'.$urut,$value->amount);
          $sheet->SetCellValue('I'.$urut,$stt[$value->status]);
       }
      });
    })->download('xls');
  }

  public function cash_count_export(Request $request)
  {
    $data = FinanceApiController::cash_count_query($request)->get();

    return Excel::create('Cash Count',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Jumlah Saldo Akhir');
        $sheet->SetCellValue('E1','Keterangan');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company->name);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('D'.$urut,$value->saldo_awal);
          $sheet->SetCellValue('E'.$urut,$value->description);
       }
      });
    })->download('xls');
  }

  public function kas_bon_export(Request $request)
  {
    $data = FinanceApiController::kas_bon_query($request)->get();

    $status = [
        1 => "Belum Disetujui",
        2 => "Sudah Disetujui",
        3 => "Selesai",
        4 => "Ditolak"
    ];

    return Excel::create('Kas Bon',function($excel) use ($data, $status){
      $excel->sheet('data', function($sheet) use ($data, $status){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Cabang');
        $sheet->SetCellValue('B1','Driver');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Jumlah Kas Bon');
        $sheet->SetCellValue('E1','Keperluan');
        $sheet->SetCellValue('F1','Status');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->company->name);
          $sheet->SetCellValue('B'.$urut,$value->driver->name);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('D'.$urut,$value->total_cash_advance);
          $sheet->SetCellValue('E'.$urut,$value->description);
          $sheet->SetCellValue('F'.$urut,$status[$value->status]);
       }
      });
    })->download('xls');
  }

  public function setting_keuangan_saldo_hutang_export(){
    $data = \App\Model\Payable::leftJoin('type_transactions','type_transactions.id','=','payables.type_transaction_id')
    ->leftJoin('contacts','contacts.id','=','payables.contact_id')
    ->leftJoin('companies','companies.id','=','payables.company_id')
    // ->where('type_transactions.slug','saldoAwal')
    ->select('payables.*','contacts.name as cname','type_transactions.name as type_trans','companies.name as coname',DB::raw("(credit-debet) as total"),DB::raw("DATEDIFF(payables.date_transaction,payables.date_tempo) as umur"))
    ->get();
    return Excel::create('Saldo Hutang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Supplier');
        $sheet->SetCellValue('E1','Cabang');
        $sheet->SetCellValue('F1','Total');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('D'.$urut,$value->cname);
          $sheet->SetCellValue('E'.$urut,$value->company->name);
          $sheet->SetCellValue('F'.$urut,$value->credit);
       }
      });
    })->download('xls');
  }
  public function setting_keuangan_saldo_piutang_export(){
    $data = \App\Model\Receivable::leftJoin('type_transactions','type_transactions.id','=','receivables.type_transaction_id')
    ->leftJoin('contacts','contacts.id','=','receivables.contact_id')
    ->leftJoin('companies','companies.id','=','receivables.company_id')
    ->select('receivables.*','contacts.name as cname','type_transactions.name as type_trans','companies.name as coname',DB::raw("(debet-credit) as total"),DB::raw("DATEDIFF(receivables.date_transaction,receivables.date_tempo) as umur"))
    ->get();
    return Excel::create('Saldo Piutang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Supplier');
        $sheet->SetCellValue('E1','Cabang');
        $sheet->SetCellValue('F1','Total');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('D'.$urut,$value->cname);
          $sheet->SetCellValue('E'.$urut,$value->company->name);
          $sheet->SetCellValue('F'.$urut,$value->credit);
       }
      });
    })->download('xls');
  }
  public function setting_keuangan_um_supplier_export(){
    $data = \App\Model\UmSupplier::leftJoin('type_transactions','type_transactions.id','=','um_suppliers.type_transaction_id')
    ->leftJoin('contacts','contacts.id','=','um_suppliers.contact_id')
    ->leftJoin('companies','companies.id','=','um_suppliers.company_id')
    ->where('type_transactions.slug','saldoAwal')
    ->select('um_suppliers.*','contacts.name as cname','companies.name as coname')
    ->get();
    return Excel::create('Setting UM Supplier',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No');
        $sheet->SetCellValue('B1','Kode');
        $sheet->SetCellValue('C1','Tanggal');
        $sheet->SetCellValue('D1','Supplier');
        $sheet->SetCellValue('E1','Cabang');
        $sheet->SetCellValue('F1','Total');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$nomor);
          $sheet->SetCellValue('B'.$urut,$value->code);
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('D'.$urut,$value->cname);
          $sheet->SetCellValue('E'.$urut,$value->coname);
          $sheet->SetCellValue('F'.$urut,$value->debet);
       }
      });
    })->download('xls');
  }

  public function draft_list_piutang_export(Request $request)
  {
    $wr="(receivables.type_transaction_id = 26 OR receivables.type_transaction_id = 2) ";
    if ($request->customer_id) {
      $wr.=" and receivables.contact_id = $request->customer_id";
    }
    if ($request->start_date_invoice && $request->end_date_invoice) {
      $start=Carbon::parse($request->start_date_invoice)->format('Y-m-d');
      $end=Carbon::parse($request->end_date_invoice)->format('Y-m-d');
      $wr.=" and receivables.date_transaction between '$start' and '$end'";
    }
    if ($request->start_due_date && $request->end_due_date) {
      $start=Carbon::parse($request->start_due_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_due_date)->format('Y-m-d');
      $wr.=" and receivables.date_tempo between '$start' and '$end'";
    }
    $item=DB::table('receivables')
      ->leftJoin('invoices','invoices.id','receivables.relation_id')
      ->leftJoin(DB::raw('(select group_concat(distinct aju_number) as aju, group_concat(distinct no_bl) as no_bl, invoice_id from job_orders group by invoice_id) as i'),'i.invoice_id','invoices.id')
      ->leftJoin('contacts','contacts.id','receivables.contact_id')
      ->whereRaw($wr)
      ->selectRaw('
        receivables.*,
        invoices.code as code_invoice,
        invoices.id as invoice_id,
        i.aju,
        i.no_bl,
        (receivables.debet-receivables.credit) as sisa,
        contacts.name as customer,
        datediff(date(now()),receivables.date_tempo) + 1 as umur,
        if( receivables.debet-receivables.credit<=0,1,if(datediff(date(now()),receivables.date_tempo)>0,2,3) ) as status_piutang')
      ->groupBy('receivables.id');

    if(isset($request->status)){
        $item->havingRaw("status_piutang = {$request->status}");
    }

    $datatable = DataTables::of($item)
    ->filterColumn('umur', function($query, $keyword) {
      $sql="datediff(date(now()),receivables.date_tempo) like ?";
      $query->whereRaw($sql, ["%{$keyword}%"]);
    })
    ->filterColumn('sisa', function($query, $keyword) {
      $sql="(receivables.debet-receivables.credit) like ?";
      $query->whereRaw($sql, ["%{$keyword}%"]);
    })
    ->editColumn('sisa', function($item){
      return number_format($item->sisa);
    })
    ->editColumn('status_piutang', function($item){
      $status=[
        1 => 'Lunas',
        2 => 'Outstanding',
        3 => 'Proses',
      ];
      return $status[$item->status_piutang];
    })
    ->rawColumns(['action','status_piutang'])
    ->skipPaging()
    ->make(true);

    $result_encoded = json_decode( json_encode($datatable) );
    $data = $result_encoded->original->data;

    return Excel::create('Saldo Piutang',function($excel) use ($data){
      $excel->sheet('data', function($sheet) use ($data){
        $sheet->SetCellValue('A1','No Invoice');
        $sheet->SetCellValue('B1','Tanggal');
        $sheet->SetCellValue('C1','Jatuh Tempo');
        $sheet->SetCellValue('D1','Customer');
        $sheet->SetCellValue('E1','Sisa');
        $sheet->SetCellValue('F1','No AJU');
        $sheet->SetCellValue('G1','B/L');
        $sheet->SetCellValue('H1','Usia Piutang');
        $sheet->SetCellValue('I1','Status');
        $nomor = 0;
        foreach ($data as $i => $value) {
          $urut=$i+2;
          $nomor++;
          $sheet->SetCellValue('A'.$urut,$value->code_invoice);
          $sheet->SetCellValue('B'.$urut,date('d-m-Y',strtotime($value->date_transaction)));
          $sheet->SetCellValue('C'.$urut,date('d-m-Y',strtotime($value->date_tempo)));
          $sheet->SetCellValue('D'.$urut,$value->customer);
          $sheet->SetCellValue('E'.$urut,$value->sisa);
          $sheet->SetCellValue('F'.$urut,$value->aju);
          $sheet->SetCellValue('G'.$urut,$value->no_bl);
          $sheet->SetCellValue('H'.$urut,$value->umur);
          $sheet->SetCellValue('I'.$urut,$value->status_piutang);
       }
      });
    })->download('xls');
  }

  public function warehouse_putaway_export(Request $request)
  {
    $wr="1=1";

    $item = DB::table('item_migrations as im')
    ->join('item_migration_details as imd','im.id','imd.header_id')
    ->join('items as i','i.id','imd.item_id')
    ->leftJoin('warehouses as wfrom','wfrom.id','im.warehouse_from_id')

    ->leftJoin('racks as rfrom','rfrom.id','im.rack_from_id')
    ->leftJoin('racks as rto','rto.id','im.rack_to_id')
    ->whereRaw($wr);

    $start_date = $request->start_date;
    $start_date = $start_date != null ? new \DateTime($start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? new \DateTime($end_date) : '';
    $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_transaction', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

    $warehouse_from = $request->warehouse_from;
    $warehouse_from = $warehouse_from != null ? $warehouse_from : '';
    $item = $warehouse_from != '' ? $item->where('im.warehouse_from_id', $warehouse_from) : $item;

    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('im.status', $status) : $item;

    $is_putaway = $request->is_putaway;
    $is_putaway = $is_putaway != null ? $is_putaway : '';
    $item = $is_putaway != '' ? $item->whereRaw('im.warehouse_from_id = im.warehouse_to_id') : $item;

    $item = $item->whereRaw('im.warehouse_from_id = im.warehouse_to_id')->selectRaw('
      im.*,
      wfrom.name as warehouse_from,
      rfrom.code as storage_from,
      rto.code as storage_to
    ')->groupBy('im.id')->orderBy('date_transaction', 'desc');

    $datatable = DataTables::of($item)
        ->editColumn('date_transaction', function($item){
            return dateView($item->date_transaction); })
        ->make(true);

    $result_encoded = json_decode( json_encode($datatable) );
    $data = $result_encoded->original->data;

    return Excel::create('Put Away',function($excel) use ($data){
        $excel->sheet('data', function($sheet) use ($data){
          $sheet->SetCellValue('A1','No.');
          $sheet->SetCellValue('B1','Gudang');
          $sheet->SetCellValue('C1','Storage Asal');
          $sheet->SetCellValue('D1','Storage Tujuan');
          $sheet->SetCellValue('E1','No. Transaksi');
          $sheet->SetCellValue('F1','Tanggal');
          $sheet->SetCellValue('G1','Status');

          $stt=[
            1 => 'Pengajuan',
            2 => 'Item Out (On Transit)',
            3 => 'Item Receipt (Done)',
          ];
          
          $nomor = 0;
          foreach ($data as $i => $value) {
            $urut=$i+2;
            $nomor++;
            $sheet->SetCellValue('A'.$urut,$nomor);
            $sheet->SetCellValue('B'.$urut,$value->warehouse_from);
            $sheet->SetCellValue('C'.$urut,$value->storage_from);
            $sheet->SetCellValue('D'.$urut,$value->storage_to);
            $sheet->SetCellValue('E'.$urut,$value->code);
            $sheet->SetCellValue('F'.$urut,$value->date_transaction);
            $sheet->SetCellValue('G'.$urut,$stt[$value->status]);
         }
        });
      })->download('xls');
  }
}
