<?php

namespace App\Http\Controllers\Api\v4;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Picking;
use App\Model\WarehouseReceiptDetail;
use App\Model\WarehouseReceipt;
use App\Model\WarehouseStockDetail;
use App\Model\Warehouse;
use App\Model\Rack;
use App\Model\Category;
use App\Model\Item;
use App\Model\StokOpnameWarehouse;
use App\Model\StorageType;
use App\Model\PurchaseRequest;
use App\Model\PurchaseOrder;
use App\Model\PalletUsage;
use App\Model\PurchaseOrderReturn;
use App\Model\Contact;
use App\Model\ItemMigration;
use App\Model\StockTransactionReport;
use Carbon\Carbon;
use DataTables;
use DB;
use Response;
use DateTime;
use App\Abstracts\Inventory\WarehouseStockDetail AS WSD;
use Illuminate\Database\Eloquent\Builder;

class OperationalWarehouseApiController extends Controller
{
    private $ctrl;

    public function __construct() {
        $this->ctrl = new \App\Http\Controllers\Api\OperationalWarehouseApiController();
    }

    public function stok_opname_datatable(Request $request) {
        return $this->ctrl->stok_opname_datatable($request);
    }

  public function warehouse_receipt_datatable(Request $request)
  {
        $item = WarehouseReceipt::with('customer:id,name', 'warehouse:id,name', 'staff:id,name', 'company:id,name')
            ->join('warehouse_receipt_details', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
            ->leftJoin('contacts', 'contacts.id', 'warehouse_receipts.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', 'warehouse_receipts.warehouse_id')
            ->groupBy('warehouse_receipts.id');

        $item = $item->select('warehouse_receipts.id', 'warehouse_receipts.warehouse_id', 'warehouse_receipts.warehouse_staff_id', 'warehouse_receipts.company_id',  'warehouse_receipts.receive_date','warehouse_receipts.code', 'warehouse_receipts.customer_id','warehouse_receipts.status', DB::raw("SUM(qty) AS total_item"), DB::raw("CONCAT('PO011') AS purchase_order_code"));

        if($request->search) {
            $item = $item->where(function($query) use ($request){
                $query->where('warehouse_receipts.code', 'LIKE', "%" . $request->search . "%");
                $query->orWhere('warehouses.name', 'LIKE', "%" . $request->search . "%");
                $query->orWhere('contacts.name', 'LIKE', "%" . $request->search . "%");
            });
        }

      $item->orderByRaw('warehouse_receipts.receive_date desc')->orderBy('warehouse_receipts.id', "DESC");

    return DataTables::of($item)
      ->filter(function($query) use ($request ) {



          if(isset($request->customer_id)) {

            $query->where('warehouse_receipts.customer_id', $request->customer_id) ;
          }

          // $start_date = $request->start_date;
          $start_date = isset($request->start_date) ? new DateTime($request->start_date) : new DateTime('07-08-1945');
          // $end_date = $request->end_date;
          $end_date = isset($request->end_date) ? new DateTime($request->end_date) : new DateTime('07-08-2145');

          $start_date = $start_date->format('Y-m-d');
          $end_date = $end_date->format('Y-m-d');
          $query->whereRaw("DATE_FORMAT(receive_date, '%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'");

          $company_id = $request->company_id;
          if(isset($request->company_id)) {
            $query->whereHas('company', function (Builder $query) use ($company_id) {
                $query->where('id', $company_id);
            });
          }

          if(isset($request->status)) {
            $query->where('status', $request->status);
          }

          if(isset($request->warehouse_id)) {

            $query->where('warehouse_id', $request->warehouse_id);
          }

          $query->limit($request->length ?: 1000000)->offset($request->start ?: 0);
      })
      ->make(true);
  }
  public function receipt_report_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and stock_transactions_report.date_transaction between '$start' and '$end'";
    }

    if ($request->warehouse_id) {
      $wr.=" and stock_transactions_report.warehouse_id = $request->warehouse_id";
    }
    
    $item = StockTransactionReport::with('stock_transaction.customer')
    ->leftJoin('items as i', 'item_id', 'i.id')
    ->leftJoin('categories as c', 'category_id', 'c.id')
    ->leftJoin('warehouses as w', 'warehouse_id', 'w.id')
    ->whereRaw($wr)
    ->selectRaw('stock_transactions_report.*, i.name AS item_name, c.name AS category_name, w.name AS warehouse_name')->orderBy('date_transaction', 'DESC')->orderBy('stock_transactions_report.id', 'DESC');

    if ($request->customer_id) {
      $item = $item->whereHas('stock_transaction', function (Builder $query) use ($request) {
          $query->where('customer_id', $request->customer_id);
      });
    }

    if ($request->is_zero) {
      $item = $item->whereRaw('jumlah_stok > 0');
    }

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html='<a ui-sref="operational_warehouse.receipt.show({id:'.$item->id.'})"><i class="fa fa-folder-o"></i></a>&nbsp;&nbsp;';

        return $html;
      })
      ->editColumn('qty_masuk', function($item){
        return number_format($item->qty_masuk);
      })
      ->editColumn('qty_keluar', function($item){
        return number_format($item->qty_keluar);
      })
      ->editColumn('jumlah_stok', function($item){
        return number_format($item->jumlah_stok);
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function rack_datatable()
  {
    $item = Rack::with('warehouse.company','storage_type')->select('racks.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html='<a ng-show="roleList.includes(\'operational_warehouse.setting.bin_location.edit\')" ng-click=\'edit('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-edit"></i></a>&nbsp;';
        // $html.='<a ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  /*
      Date : 16-04-2020
      Description : Daftar gudang
      Developer : Didin
      Status : Create
  */
  public function warehouseDatatable(Request $request)
  {
    $item = DB::table('warehouses');

    return DataTables::of($item)
    ->filter(function ($query ) use ($request ){
          if($request->filled('name')) {
              $request->name = addslashes($request->name);
              $query->whereRaw("name LIKE '%{$request->name}%'");
          }

          $query
          ->select('warehouses.id', 'warehouses.name')
          ->offset($request->start ?? 0)
          ->limit($request->length ?? 1000000);
      })
      ->make(true);
  }
  /*
      Date : 16-04-2020
      Description : Daftar stok pada gudang
      Developer : Didin
      Status : Create
  */
  public function warehouseStockDatatable(Request $request)
  {
    $item = DB::table('warehouses AS W')
    ->leftJoin(DB::raw('(SELECT S1.warehouse_id, SUM(qty_masuk - qty_keluar) AS qty FROM stock_transactions S1 GROUP BY S1.warehouse_id) AS S'), 'S.warehouse_id', 'W.id')
    ->leftJoin(DB::raw('(SELECT warehouse_id, MAX(date_transaction) AS last_in FROM stock_transactions S2 GROUP BY S2.warehouse_id) AS ST'), 'ST.warehouse_id', 'W.id')
    ->select('W.id', 'W.name', DB::raw('IFNULL(S.qty, 0) AS qty'), 'ST.last_in')
    ->groupBy('W.id');

    return DataTables::of($item)
    ->filter(function ($query ) use ($request ){
          if($request->filled('name')) {
              $request->name = addslashes($request->name);
              $query->whereRaw("W.name LIKE '%{$request->name}%'");
          }

          $query
          ->offset($request->start ?? 0)
          ->limit($request->length ?? 1000000);
      })
      ->make(true);
  }
  
  public function receipt_detail_datatable()
  {
    $wr="1=1";
    $item = WarehouseReceiptDetail::with('header','piece','rack')
    ->leftJoin('warehouse_receipts','warehouse_receipts.id','=','warehouse_receipt_details.header_id')
    ->select('warehouse_receipt_details.*','warehouse_receipts.is_export');

    return DataTables::of($item)
      ->addColumn('action_choose', function($item){
        $html='<a ng-click=\'chooseReceiptDetail('.json_encode($item,JSON_HEX_APOS).')\' class="btn btn-xs btn-success">Pilih</a>';
        return $html;
      })
      ->editColumn('is_export', function($item){
        $stt=[
          1 => 'Export',
          0 => 'Import',
        ];
        return $stt[$item->is_export];
      })
      ->editColumn('imposition', function($item){
        $stt=[
          1 => 'Kubikasi',
          2 => 'Tonase',
          3 => 'Item',
        ];
        return $stt[$item->imposition];
      })
      ->rawColumns(['action_choose'])
      ->make(true);
  }

  public function pallet_category_datatable()
  {
    $item = Category::leftJoin('categories as parent','parent.id','categories.parent_id')->where('categories.is_pallet', 1)->selectRaw('categories.*,parent.name as parent');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html='<a ng-show="roleList.includes(\'operational_warehouse.setting.pallet.edit\')" ng-click=\'edit('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-edit"></i></a>&nbsp;';
        if ($item->parent_id) {
          $html.='<a ng-show="roleList.includes(\'operational_warehouse.setting.pallet.delete\')" ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
        }
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function storage_type_datatable()
  {
    $item = StorageType::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html='<a ng-show="roleList.includes(\'operational_warehouse.setting.storage.edit\')" ng-click=\'edit('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-edit"></i></a>&nbsp;';
        $html.='<a ng-show="roleList.includes(\'operational_warehouse.setting.storage.delete\')" ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function master_pallet_datatable()
  {
    $item = Item::with('piece')
    ->leftJoin('categories','categories.id','items.category_id')
    ->where('categories.is_pallet', 1)
    ->selectRaw('items.id, items.name, items.description, categories.name AS category');

    return DataTables::of($item)
      ->make(true);
  }

  public function general_item_datatable(Request $request)
  {
    $item = Item::with('piece:id,name')
        ->where('is_active', 1)
        ->selectRaw('id, `code`, `name`, piece_id, description, `long`, wide, height, volume');



    if(isset($request->customer_id)) {
      $item = $item->where('customer_id', $request->customer_id);
    }
    // if(isset($request->warehouse_id)) {
    //   $item = $item->where('warehouse_id', $request->warehouse_id);
    // }
    return DataTables::of($item)
    ->make(true);
  }
  public function master_item_datatable(Request $request)
  {
    $item = Item::with('piece')->leftJoin('categories','categories.id','items.category_id')->where('categories.is_pallet', 0)->selectRaw('items.*,categories.name as category');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html='<a ng-click=\'edit('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-edit"></i></a>&nbsp;';
        $html.='<a ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html='<a ng-click=\'choosePallet('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-check"></i></a>&nbsp;';
        return $html;
      })

      ->rawColumns(['action','action_choose'])
      ->make(true);
  }

  public function item_warehouse_datatable(Request $request)
  {
       $item = new \App\Http\Controllers\Api\OperationalWarehouseApiController();
       return $item->item_warehouse_datatable($request);
  }

  public function item_in_picking_datatable(Request $request)
  {
       $request->show_picking = 1;
       $item = new \App\Http\Controllers\Api\OperationalWarehouseApiController();
       return $item->item_warehouse_datatable($request);
  }

  public function validasi_item_datatable($id)
  {
    // Customer ID is mandatory
    if( DB::table('job_orders')->where('id', $id)->first() == null ) {
      return Response::json(['message' => 'Transaksi tidak ditemukan'], 422);
    }
    DB::getQueryLog();
    $item = JobOrderDetail::where('job_order_details.header_id', $id)
      ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'job_order_details.warehouse_receipt_detail_id')
      ->join('warehouse_receipts', 'warehouse_receipt_details.header_id', 'warehouse_receipts.id')
      ->leftJoin('pieces', 'pieces.id', 'job_order_details.piece_id')
      ->leftJoin('items', 'items.id', 'job_order_details.item_id')
      ->leftJoin('racks', 'racks.id', 'job_order_details.rack_id')
      ->select(
        'job_order_details.id', 
        'warehouse_receipt_detail_id', 
        'job_order_details.warehouse_receipt_detail_id', 
        'job_order_details.rack_id', 
        'items.code AS item_code', 
        'racks.code AS rack_code', 
        'job_order_details.item_id', 
        'job_order_details.item_name', 
        'job_order_details.qty', 
        'job_order_details.volume', 
        'job_order_details.weight', 
        'job_order_details.long', 
        'job_order_details.wide', 
        'job_order_details.high', 
        'job_order_details.piece_id', 
        'job_order_details.imposition', 
        'job_order_details.description', 
        'warehouse_receipt_details.header_id AS warehouse_receipt_id', 'job_order_details.piece_id', 
        'pieces.name AS piece_name', 
        'warehouse_receipts.code AS warehouse_receipt_code',
        DB::raw("(SELECT IFNULL(SUM(qty), 0) FROM warehouse_stock_details WHERE warehouse_receipt_id = warehouse_receipts.id AND item_id = job_order_details.item_id AND rack_id = job_order_details.rack_id) AS stock")
    );

    return DataTables::of($item)
      ->make(true);
  }

  public function pallet_purchase_request_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->company_id) {
      $wr.=" and company_id = $request->company_id";
    }

    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and date_request between '$start' and '$end'";
    }
    $wr.=" and is_pallet = 1";
    $item = PurchaseRequest::with('company','supplier','warehouse')->whereRaw($wr);

    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where('status', $status) : $item;
    $item = $item->select('purchase_requests.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational_warehouse.pallet.purchase_request.detail')\" ui-sref='operational_warehouse.pallet_purchase_request.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->status==1) {
          $html.="<a ng-show=\"roleList.includes('operational_warehouse.pallet.purchase_request.edit')\" ui-sref='operational_warehouse.pallet_purchase_request.edit({id:$item->id})' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('operational_warehouse.pallet.purchase_request.delete')\" ng-click='deletes($item->id)' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->editColumn('date_request', function($item){
        return dateView($item->date_request);
      })
      ->editColumn('date_needed', function($item){
        return dateView($item->date_needed);
      })
      ->editColumn('status', function($item){
        $stt=[
          0 => '<span class="badge badge-danger">Ditolak</span>',
          1 => '<span class="badge badge-warning">Belum Persetujuan</span>',
          2 => '<span class="badge badge-primary">Sudah Persetujuan</span>',
          3 => '<span class="badge badge-suc<!--  -->ess">Purchase Order</span>',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action','status'])
      ->make(true);
  }

  public function pallet_purchase_order_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->po_status) {
      $wr.=" and purchase_orders.po_status = $request->po_status";
    }
    if ($request->vehicle_maintenance_isnull) {
      $wr.=" and purchase_orders.vehicle_maintenance_id IS NULL";
    }
    if ($request->warehouse_id) {
      $wr.=" and purchase_orders.warehouse_id = $request->warehouse_id";
    }
    if ($request->company_id) {
      $wr.=" and purchase_orders.company_id = $request->company_id";
    }
    if ($request->supplier_id) {
      $wr.=" and purchase_orders.supplier_id = $request->supplier_id";
    }

    if ($request->not_po_retur) {
      $st=DB::select("SELECT concat('0,',group_concat(distinct purchase_order_id)) as polist FROM purchase_order_returns")[0];
      $wr.=" and purchase_orders.id not in ($st->polist)";
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and purchase_orders.po_date between '$start' and '$end'";
    }

    $wr.=" and purchase_requests.is_pallet = 1";

    $item = PurchaseOrder::with('company','supplier','warehouse')
    ->leftJoin('purchase_requests','purchase_requests.id','purchase_orders.purchase_request_id')
    ->whereRaw($wr)->select('purchase_orders.*');

    return DataTables::of($item)
      ->addColumn('action_choose', function($item){
        $html='<button ng-click="choosePo('.$item->id.')" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Pilih</button>';
        return $html;
      })
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational_warehouse.pallet.purchase_order.detail')\" ui-sref='operational_warehouse.pallet_purchase_order.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->editColumn('po_date', function($item){
        return dateView($item->po_date);
      })
      ->editColumn('po_status', function($item){
        $stt=[
          1 => '<span class="badge badge-success">Purchase Order</span>',
          2 => '<span class="badge badge-primary">Diterima</span>',
        ];
        return $stt[$item->po_status];
      })
      ->rawColumns(['action','po_status', 'action_choose'])
      ->make(true);
  }

  public function pallet_receipt_datatable(Request $request)
  {
    // $item = Receipt::with('company','purchase_order','lists','lists.warehouse')->select('receipts.*');
    $wr="1=1";
    $source=[
      1 => 'Purchase Order',
      2 => 'Retur Pembelian',
      3 => 'Retur Penjualan',
      4 => 'Transfer / Mutasi',
      5 => 'Operational',
    ];
    if ($request->company_id) {
      $wr.=" and receipts.company_id = $request->company_id";
    }
    if ($request->warehouse_id) {
      $wr.=" and receipt_lists.warehouse_id = $request->warehouse_id";
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and receipt_lists.receive_date between '$start' and '$end'";
    }

    $item=DB::table('receipts')
    ->leftJoin('companies','companies.id','receipts.company_id')
    ->leftJoin('purchase_orders','purchase_orders.id','receipts.po_id')
    ->leftJoin(DB::raw("(select group_concat(ws.name separator ',<br>') as warehouse, group_concat(receipt_lists.receive_date separator ',<br>') as receive_date, receipt_lists.header_id from receipt_lists left join warehouses as ws on ws.id = receipt_lists.warehouse_id group by receipt_lists.header_id) rl"),'rl.header_id','receipts.id')
    ->selectRaw('
      receipts.*,
      companies.name as company,
      purchase_orders.code as po_code,
      purchase_orders.po_date as po_date,
      group_concat(warehouses.name separator \',\n\') as wr,
      group_concat(receipt_lists.receive_date separator \',\n\') as receive_date
      ')->groupBy('receipts.id');

      return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational_warehouse.pallet.receipt.detail')\" ui-sref='operational_warehouse.pallet_receipt.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->editColumn('po_date', function($item){
        return dateView($item->po_date);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => '<span class="badge badge-warning">Belum Lengkap</span>',
          2 => '<span class="badge badge-success">Sudah Lengkap</span>',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action','status','warehouse','receive_date','wr','receive_date'])
      ->toJson();
  }
  public function pallet_using_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->customer_id) {
      $wr.=" and pallet_usages.customer_id = $request->customer_id";
    }
    if ($request->warehouse_id) {
      $wr.=" and pallet_usages.warehouse_id = $request->warehouse_id";
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and pallet_usages.using_date between '$start' and '$end'";
    }

    $item=DB::table('pallet_usages')
    ->leftJoin('warehouses','warehouses.id','pallet_usages.warehouse_id')
    ->leftJoin('contacts','contacts.id','pallet_usages.customer_id')
    ->leftJoin('pallet_usage_details','pallet_usages.id','pallet_usage_details.header_id')
    ->leftJoin('items','items.id','pallet_usage_details.item_id')
    ->whereRaw($wr)
    ->selectRaw('
    pallet_usages.*,
    warehouses.name as wr,
    contacts.name as customer,
    group_concat(items.name) as item_list
    ')->groupBy('pallet_usages.id');
    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational_warehouse.pallet.using.detail')\" ui-sref='operational_warehouse.pallet_using.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->editColumn('using_date', function($item){
        return dateView($item->using_date);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => '<span class="badge badge-warning">Pengajuan</span>',
          2 => '<span class="badge badge-success">Storage Used</span>',
          3 => '<span class="badge badge-info">Shipping Used</span>',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action','status','item_list'])
      ->toJson();
  }

  public function pallet_po_return_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->po_status) {
      $wr.=" and purchase_orders.po_status = $request->po_status";
    }
    if ($request->status) {
      $wr.=" and purchase_order_returns.status = $request->status";
    }
    if ($request->warehouse_id) {
      $wr.=" and purchase_order_returns.warehouse_id = $request->warehouse_id";
    }
    if ($request->vehicle_maintenance_isnull) {
      $wr.=" and purchase_orders.vehicle_maintenance_id IS NULL";
    }

    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and purchase_order_returns.date_transaction between '$start' and '$end'";
    }

    $wr.=" and purchase_requests.is_pallet = 1";

    $item = DB::table('purchase_order_returns')
    ->leftJoin('purchase_orders','purchase_orders.id','purchase_order_returns.purchase_order_id')
    ->leftJoin('contacts','contacts.id','purchase_orders.supplier_id')
    ->leftJoin('purchase_requests','purchase_requests.id','purchase_orders.purchase_request_id')
    ->leftJoin('warehouses','warehouses.id','purchase_order_returns.warehouse_id')
    ->leftJoin(DB::raw('(select count(id) as jml, header_id from purchase_order_return_details group by header_id) pod'),'pod.header_id','purchase_order_returns.id')
    ->whereRaw($wr)
    ->selectRaw('
      purchase_order_returns.*,
      purchase_orders.code as po_code,
      purchase_orders.po_date as po_date,
      warehouses.name as warehouse_name,
      contacts.name as supplier,
      pod.jml
    ');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational_warehouse.pallet.po_return.detail')\" ui-sref='operational_warehouse.pallet_po_return.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->status==1) {
          $html.='<a ng-show="roleList.includes(\'operational_warehouse.pallet.po_return.delete\')" ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
        }
        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html='<button ng-click="choosePoReturn('.$item->id.')" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Pilih</button>';
        return $html;
      })
      ->editColumn('date_transaction', function($item){
        return dateView($item->date_transaction);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => '<span class="badge badge-success">Pengajuan</span>',
          2 => '<span class="badge badge-primary">Dikirimkan</span>',
          3 => '<span class="badge badge-info">Diterima Sebagian</span>',
          4 => '<span class="badge badge-info">Diterima Lengkap</span>',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action','status','action_choose'])
      ->make(true);
  }
  public function pallet_sales_order_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->status) {
      $wr.=" and sales_orders.status = $request->status";
    }
    if ($request->customer_id) {
      $wr.=" and sales_orders.customer_id = $request->customer_id";
    }
    if ($request->company_id) {
      $wr.=" and sales_orders.company_id = $request->company_id";
    }
    if ($request->warehouse_id) {
      $wr.=" and sales_orders.warehouse_id = $request->warehouse_id";
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and sales_orders.date_transaction between '$start' and '$end'";
    }
    if ($request->not_so_retur) {
      $st=DB::select("select concat('0,',group_concat(distinct sales_order_id)) as polist from sales_order_returns")[0];
      $wr.=" and sales_orders.id not in ($st->polist)";
    }

    $item = DB::table('sales_orders')
    ->leftJoin('companies','companies.id','sales_orders.company_id')
    ->leftJoin('warehouses','warehouses.id','sales_orders.warehouse_id')
    ->leftJoin('contacts','contacts.id','sales_orders.customer_id')
    ->whereRaw($wr)
    ->selectRaw('
      sales_orders.*,
      companies.name as company_name,
      warehouses.name as warehouse_name,
      contacts.name as customer
    ');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational_warehouse.pallet.sales_order.detail')\" ui-sref='operational_warehouse.pallet_sales_order.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->status==1) {
          $html.='<a ng-show="roleList.includes(\'operational_warehouse.pallet.sales_order.delete\')" ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
        }

        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html='<button ng-click="chooseSo('.$item->id.')" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Pilih</button>';
        return $html;
      })

      ->editColumn('date_transaction', function($item){
        return dateView($item->date_transaction);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => '<span class="badge badge-success">Pengajuan</span>',
          2 => '<span class="badge badge-primary">Sales Order</span>',
          3 => '<span class="badge badge-info">Selesai</span>',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action','status','action_choose'])
      ->make(true);
  }
  public function pallet_sales_order_return_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->status) {
      $wr.=" and sales_orders.status = $request->status";
    }
    if ($request->vehicle_maintenance_isnull) {
      $wr.=" and purchase_orders.vehicle_maintenance_id IS NULL";
    }
    if ($request->customer_id) {
      $wr.=" and sales_orders.customer_id = $request->customer_id";
    }
    if ($request->warehouse_id) {
      $wr.=" and sales_order_returns.warehouse_id = $request->warehouse_id";
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and sales_order_returns.date_transaction between '$start' and '$end'";
    }

    $item = DB::table('sales_order_returns')
    ->leftJoin('sales_orders','sales_orders.id','sales_order_returns.sales_order_id')
    ->leftJoin('warehouses','warehouses.id','sales_order_returns.warehouse_id')
    ->leftJoin('contacts','contacts.id','sales_orders.customer_id')
    ->leftJoin(DB::raw('(select count(id) as jml, header_id from sales_order_return_details group by header_id) pod'),'pod.header_id','sales_order_returns.id')
    ->whereRaw($wr)
    ->selectRaw('
      sales_order_returns.*,
      sales_orders.code as so_code,
      sales_orders.date_transaction as so_date,
      warehouses.name as warehouse_name,
      contacts.name as customer,
      pod.jml
    ');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational_warehouse.pallet.sales_order_return.detail')\" ui-sref='operational_warehouse.pallet_sales_order_return.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->status==1) {
          $html.='<a ng-show="roleList.includes(\'operational_warehouse.pallet.sales_order_return.delete\')" ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
        }
        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html='<button ng-click="chooseSoReturn('.$item->id.')" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Pilih</button>';
        return $html;
      })

      ->editColumn('date_transaction', function($item){
        return dateView($item->date_transaction);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => '<span class="badge badge-success">Pengajuan</span>',
          2 => '<span class="badge badge-primary">Sales Order Return</span>',
          3 => '<span class="badge badge-info">Item Return (Done)</span>',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action','status','action_choose'])
      ->make(true);
  }
  public function pallet_migration_datatable(Request $request)
  {
    $wr="1=1";

    if ($request->warehouse_id1) {
      $wr.=" and im.warehouse_from_id = $request->warehouse_id1";
    }
    if ($request->warehouse_id2) {
      $wr.=" and im.warehouse_to_id = $request->warehouse_id2";
    }
    if ($request->storage_id1) {
      $wr.=" and rfrom.id = $request->storage_id1";
    }
    if ($request->storage_id2) {
      $wr.=" and rto.id = $request->storage_id2";
    }
    if ($request->start_date && $request->end_date) {
      $start=Carbon::parse($request->start_date)->format('Y-m-d');
      $end=Carbon::parse($request->end_date)->format('Y-m-d');
      $wr.=" and im.date_transaction between '$start' and '$end'";
    }

    $item = DB::table('item_migrations as im')
    ->join('item_migration_details as imd','im.id','imd.header_id')
    ->join('items as i','i.id','imd.item_id')
    ->join('categories as c','i.category_id','c.id')
    ->leftJoin('warehouses as wfrom','wfrom.id','im.warehouse_from_id')
    ->leftJoin('warehouses as wto','wto.id','im.warehouse_to_id')
    ->leftJoin('racks as rfrom','rfrom.warehouse_id','wfrom.id')
    ->leftJoin('racks as rto','rto.warehouse_id','wto.id')
    ->whereRaw($wr)
    ->where('c.is_pallet', 1)
    ->selectRaw('
      im.*,
      wfrom.name as warehouse_from,
      wto.name as warehouse_to,
      rfrom.code as storage_from,
      rto.code as storage_to
    ');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('operational_warehouse.pallet.migration.detail')\" ui-sref='operational_warehouse.pallet_migration.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->status==1) {
          $html.='<a ng-show="roleList.includes(\'operational_warehouse.pallet.migration.delete\')" ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
        }
        return $html;
      })
      ->editColumn('date_transaction', function($item){
        return dateView($item->date_transaction);
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => '<span class="badge badge-success">Pengajuan</span>',
          2 => '<span class="badge badge-primary">Item Out (On Transit)</span>',
          3 => '<span class="badge badge-info">Item Receipt (Done)</span>',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action','status'])
      ->make(true);
  }
   public function mutasi_transfer_datatable(Request $request)
  {
    $wr="1=1";

    $item = ItemMigration::with('warehouse_from:id,name','warehouse_to:id,name', 'rack_from:id,code', 'rack_to:id,code')
    ->join('item_migration_details', 'item_migrations.id', 'item_migration_details.header_id')
    ->whereRaw($wr)
    ->groupBy('item_migrations.id');

    $item = $item->whereRaw('warehouse_from_id != warehouse_to_id');

    $item = $item->selectRaw('
      item_migrations.id,
      item_migrations.code,
      date_transaction,
      status,
      warehouse_from_id,
      warehouse_to_id,
      rack_from_id,
      rack_to_id,
      SUM(item_migration_details.qty) AS total_item,
      IF(status = 1, "Pengajuan", IF(status = 2, "Item Out (On Transit)", "Item Receipt (Done)")) AS status_name
    ')->groupBy('id')->orderBy('date_transaction', 'desc')->orderBy('id', 'desc');

    return DataTables::of($item)
      ->filter(function ($query) use ($request ){
          $start_date = $request->start_date;
          $start_date = $start_date != null ? new DateTime($start_date) : new DateTime('07-08-1945');;
          $end_date = $request->end_date;
          $end_date = $end_date != null ? new DateTime($end_date) : new DateTime('07-08-2145');;

          $start_date = $start_date->format('Y-m-d');
          $end_date = $end_date->format('Y-m-d');

          $query->whereRaw("(date_transaction BETWEEN '$start_date' AND '$end_date')");

          if(isset($request->warehouse_from_id)) {
              $query->where('warehouse_from_id', $request->warehouse_from_id);
          }
          if(isset($request->warehouse_to_id)) {
              $query->where('warehouse_to_id', $request->warehouse_to_id);
          }

          if(isset($request->status)) {
              $query->where('status', $request->status);
          }

           $query->limit($request->length ?: 1000000)->offset($request->start ?: 0 );
      })
      ->editColumn('date_transaction', function($item){
        return dateView($item->date_transaction);
      })
      ->make(true);
  }

    public function putaway_datatable(Request $request)
    {
        return $this->ctrl->putaway_datatable($request);
    }

  public function stocklist_datatable(Request $request)
  {
    $wr="1=1";

    $item = WSD::stocklist($request->all());

    $item = $item->selectRaw('
      warehouse_stock_details.no_surat_jalan, 
      warehouse_stock_details.customer_name,
      warehouse_stock_details.sender, 
      warehouse_stock_details.receiver, 
      warehouse_stock_details.name, 
      warehouse_stock_details.warehouse_name,
      warehouse_stock_details.receive_date,
      warehouse_stock_details.qty
    ');

    return DataTables::of($item)
      ->editColumn('qty', function($item){
        return formatNumber($item->qty);
      })
      ->editColumn('receive_date', function($item){
        return dateView($item->receive_date);
      })
      ->filter(function ($query ) use ($request ){

          if(!isset($request->start_date)) {
            $request->start_date = "17-08-1945";
          }
          if(!isset($request->end_date)) {
            $request->end_date = "17-08-2145";
          }

          $start = Carbon::parse($request->start_date)->format('Y-m-d');
          $end = Carbon::parse($request->end_date)->format('Y-m-d');
          $query->whereRaw("DATE_FORMAT(receive_date, '%Y-%m-%d') BETWEEN '$start' AND '$end'");

    if(isset($request->warehouse_id)) {
      $query->where('warehouses.id', $request->warehouse_id);
    }


    if(isset($request->customer_id)) {
    $query->where('warehouse_receipts.customer_id', $request->customer_id);
    }


    
      $query->limit($request->length ?: 1000000)->offset($request->start ?: 0 );


      })
      ->make(true);
  }

  /*
      Date : 20-04-2020
      Description : Daftar stok barang per customer, per item dan 
                    per gudang
      Developer : Didin
      Status : Create
  */
  public function customerStockDatatable(Request $request)
  {
    $wr="1=1";

    $item = DB::table('stock_transactions_report')
    ->join('stock_transactions', 'stock_transactions.id', 'stock_transactions_report.header_id')
    ->join('warehouse_receipt_details', 'warehouse_receipt_details.id', 'stock_transactions.warehouse_receipt_detail_id')
    ->join('warehouse_receipts', 'warehouse_receipts.id', 'warehouse_receipt_details.header_id')
    ->join('warehouses', 'warehouses.id', 'stock_transactions_report.warehouse_id')
    ->join('items', 'items.id', 'stock_transactions_report.item_id')
    ->join('contacts', 'warehouse_receipts.customer_id', 'contacts.id');

    $item = $item->selectRaw('
      contacts.name AS customer_name,
      contacts.id AS customer_id,
      items.name AS item_name, 
      items.id AS item_id, 
      warehouses.id AS warehouse_id,
      SUM(stock_transactions_report.qty_masuk - stock_transactions_report.qty_keluar) AS qty
    ');

    $item = $item->groupBy(
      'warehouse_receipts.customer_id', 
      'stock_transactions_report.warehouse_id',
      'stock_transactions_report.item_id'
    );

    return DataTables::of($item)
      ->filter(function ($query ) use ($request ){

    if(isset($request->warehouse_id)) {
      $query->where('warehouses.id', $request->warehouse_id);
    }


    if(isset($request->customer_id)) {
    $query->where('warehouse_receipts.customer_id', $request->customer_id);
    }

    if($request->filled('customer_name')) {
        $request->customer_name = addslashes($request->customer_name);
        $query->whereRaw("contacts.name LIKE '%{$request->customer_name}%'");
    }

    if($request->filled('item_name')) {
        $request->item_name = addslashes($request->item_name);
        $query->whereRaw("items.name LIKE '%{$request->item_name}%'");
    }
    
    $query->limit($request->length ?: 1000000)->offset($request->start ?: 0 );


      })
      ->make(true);
  }

    public function picking_datatable(Request $request){
        return $this->ctrl->picking_datatable($request);
    }
}
