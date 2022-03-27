<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Picking;
use App\Abstracts\Inventory\WarehouseReceiptDetail;
use App\Model\WarehouseReceipt;
use App\Model\WarehouseStockDetail;
use App\Model\Warehouse;
use App\Model\Rack;
use App\Model\JobOrderDetail;
use App\Model\Category;
use App\Model\Item;
use App\Model\StokOpnameWarehouse;
use App\Model\StorageType;
use App\Model\PurchaseRequest;
use App\Model\PurchaseOrder;
use App\Model\PalletUsage;
use App\Model\PurchaseOrderReturn;
use App\Model\Contact;
use App\Model\StockTransactionReport;
use App\Abstracts\Inventory\Packaging;
use App\Abstracts\Inventory\StockTransaction AS ST;
use App\Abstracts\Inventory\Item AS I;
use Carbon\Carbon;
use Response;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use App\Abstracts\Inventory\WarehouseStockDetail AS WSD;
use App\Abstracts\Inventory\ItemMigrationType;
use App\Abstracts\PurchaseOrder AS PO;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Abstracts\WarehouseReceipt AS WR;

class OperationalWarehouseApiController extends Controller
{
    /*
        Date : 18-03-2020
        Description : Menampilkan daftar penerimaan barang dalam format 
        datatable
        Developer : Didin
        Status : Create
    */
    public function warehouse_receipt_datatable(Request $request)
    {
        WR::setEmptyDescription();
        $item = WarehouseReceipt::with('customer:id,name','company:id,name','warehouse:id,name','staff')
        ->leftJoin(DB::raw("(select sum(qty) as total,header_id from warehouse_receipt_details group by header_id) det"),'det.header_id','=','warehouse_receipts.id')
        ->when(!auth()->user()->is_admin, function ($query) {
            $query->where('warehouse_receipts.company_id', auth()->user()->company_id);
        });
        
        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('receive_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;
        
        $company_id = $request->company_id;
        if(isset($request->company_id)) {
            $item = $item->whereHas('company', function (Builder $query) use ($company_id) {
                $query->where('id', $company_id);
            });
        }
        
        if(isset($request->status)) {
            $item = $item->where('status', $request->status);
        }
        
        $warehouse_id = $request->warehouse_id;
        $warehouse_id = $warehouse_id != null ? $warehouse_id : '';
        $item = $warehouse_id != '' ? $item->where('warehouse_id', $request->warehouse_id) : $item;
        
        $customer_id = $request->customer_id;
        $customer_id = $customer_id != null ? $customer_id : '';
        $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;
        
        $item = $item
        ->whereNull('deleted_at')
        ->select('warehouse_receipts.*','det.*');
        if($request->draw == 1) {      
            $item->orderBy('id', 'desc');
        }

        if($request->filled('purchase_order_id'))
            $item->where('warehouse_receipts.purchase_order_id', $request->purchase_order_id);

        if($request->filled('item_migration_id')) {
            $migrations = DB::table('item_migration_receipts');
            $migrations = $migrations->whereRaw("item_migration_id = " . $request->item_migration_id);
            $migrations = $migrations->select('warehouse_receipt_id');
            $migrations = $migrations->toSql();
            $item = $item->whereRaw("warehouse_receipts.id IN ($migrations)");
        }

        if($request->filled('voyage_schedule_id')) {
            $voyage = DB::table('voyage_receipts');
            $voyage = $voyage->whereRaw("voyage_schedule_id = " . $request->voyage_schedule_id);
            $voyage = $voyage->select('warehouse_receipt_id');
            $voyage = $voyage->toSql();
            $item = $item->whereRaw("warehouse_receipts.id IN ($voyage)");
        }

        if($request->filled('sales_order_return_id')) {
            $salesOrderReturn = DB::table('sales_order_return_receipts');
            $salesOrderReturn = $salesOrderReturn->whereRaw("sales_order_return_id = " . $request->sales_order_return_id);
            $salesOrderReturn = $salesOrderReturn->select('warehouse_receipt_id');
            $salesOrderReturn = $salesOrderReturn->toSql();
            $item = $item->whereRaw("warehouse_receipts.id IN ($salesOrderReturn)");
        }

        if($request->is_pallet == 1) {
            $pallet = DB::table('items')
            ->join('categories', 'categories.id', 'items.category_id')
            ->join('categories AS parents', 'categories.parent_id', 'parents.id')
            ->whereRaw('categories.is_pallet = 1 OR parents.is_pallet = 1')
            ->select('items.id');

            $pallet = $pallet->toSql();

            $details = DB::table('warehouse_receipt_details')
            ->select('header_id')
            ->whereRaw("item_id IN ($pallet)");
            $details = $details->toSql();

            $item = $item->whereRaw("warehouse_receipts.id IN ($details)");
        }

        if($request->is_purchase_order == 1) {
            if($request->is_merchandise == 1) {
                $po = PO::query(["is_merchandise" => 1])->select("purchase_orders.id")->pluck("id");
                $item = $item->whereIn("warehouse_receipts.purchase_order_id", $po);
            }
        }

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html='';
            if($item->status == 0 || $item->status == 2) {
                
                $html.="<a ng-show=\"roleList.includes('operational_warehouse.receive.approve')\" ng-click='approve($item->id)' data-toggle='tooltip' title='Validasi'><span class='fa fa-check'></span>&nbsp;&nbsp;</a>";
            }
            
            $html.='<a title="Detail" ng-show="roleList.includes(\'operational_warehouse.receive.detail\')" ui-sref="operational_warehouse.receipt.show({id:'.$item->id.'})"><i class="fa fa-folder-o"></i></a>&nbsp;&nbsp;';
            if($item->status == 0 || $item->status == 2) {
                
                $html.='<a title="Edit" ng-show="roleList.includes(\'operational_warehouse.receive.edit\')" ui-sref="operational_warehouse.receipt.edit({id:'.$item->id.'})"><i class="fa fa-edit"></i></a>';
            }
            
            if($item->status != 1) {
                $html.="&nbsp;&nbsp<a ng-click='delete($item->id)' data-toggle='tooltip' title='Hapus'><span class='fa fa-trash'></span>&nbsp;&nbsp;</a>";
            }
            
            return $html;
            
        })
        ->addColumn('total', function($item){
            return formatNumber($item->total);
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    /*
        Date : 18-03-2020
        Description : Menampilkan daftar penerimaan barang dalam format 
        datatable
        Developer : Didin
        Status : Create
    */

    public function packaging_datatable(Request $request)
    {

        $dt = Packaging::query($request->all());
        $dt = $dt->select('packagings.id', 'packagings.code', 'packagings.date', 'packagings.description', 'companies.name AS company_name', 'warehouses.name AS warehouse_name');
        $dt = $dt->orderBy("packagings.id", "DESC"); 


        return DataTables::of($dt)->make(true);
    }

    public function receipt_report_datatable(Request $request)
    {
        $item = ST::query($request->all());
        $item = $item->select('warehouse_receipts.status AS warehouse_receipt_status', 'stock_transactions.description', 'warehouse_receipts.code AS warehouse_receipt_code', 'stock_transactions.date_transaction', 'warehouses.name AS warehouse_name', 'warehouse_receipt_details.item_name', 'stocks.qty_sisa', 'stock_transactions.qty_masuk', 'stock_transactions.qty_keluar', 'contacts.name AS customer_name', 'warehouse_receipts.id AS warehouse_receipt_id', 'racks.code AS rack_code');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html='<a ui-sref="operational_warehouse.receipt.show({id:'.$item->warehouse_receipt_id.'})"><i class="fa fa-folder-o"></i></a>&nbsp;&nbsp;';
            
            return $html;
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function rack_datatable(Request $request)
    {
        $item = Rack::with('warehouse.company','storage_type')->select('racks.*');
        
        if($request->filled('warehouse_id')) {
            $item->where('racks.warehouse_id', $request->warehouse_id);
        }

        if($request->filled('is_used_only')) {
            if($request->is_used_only == 1) {
                $item->where(function($q) use ($request){
                    $q->where('capacity_volume_used' , '>', 0)
                    ->orWhere('capacity_tonase_used' , '>', 0);
                });
            }
        }

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html='<a ui-sref=\'operational_warehouse.bin_location.show({id:' . $item->id . '})\'><i class="fa fa-folder-o"></i></a>&nbsp;&nbsp;<a ng-show="roleList.includes(\'operational_warehouse.setting.bin_location.edit\')" ng-click=\'edit('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-edit"></i></a>';
            return $html;
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function warehouse_datatable(Request $request)
    {
        $item = Warehouse::with('company:id,name')->select('warehouses.*');

        if($request->filled('company_id')) {
            $item->where('warehouses.company_id', $request->company_id);
        }
        
        return DataTables::of($item)
        ->editColumn('is_active', function($item){
            $stt=[
                0 => '<span class="badge badge-warning">Non-aktif</span>',
                1 => '<span class="badge badge-success">Aktif</span>',
            ];
            return $stt[$item->is_active];
        })
        ->rawColumns(['is_active'])
        ->make(true);
    }
    public function warehouse_receipt_detail_datatable(Request $request)
    {
        $wr="1=1";
        $item = WarehouseReceiptDetail::query();
        $item = $item->select(
            'warehouse_receipt_details.id', 
            'warehouse_receipts.receive_date',
            'warehouse_receipts.code AS no_surat_jalan',
            'warehouse_receipt_details.item_name AS name', 
            'warehouse_receipt_details.qty', 
            'receipt_quality_statuses.name AS quality_status_name', 
            'receipt_quality_statuses.slug AS quality_status_slug', 
            'companies.name AS company_name', 'warehouses.name AS warehouse_name'
        );

        $item = DB::query()->fromSub($item, 'warehouse_receipt_details');
        
        return DataTables::of($item)
        ->make(true);
    }
    
    public function pallet_category_datatable(Request $request)
    {
        $item = Category::leftJoin('categories as parent','parent.id','categories.parent_id')->where('categories.is_pallet', 1)->selectRaw('categories.*,parent.name as parent');
        
        return DataTables::of($item)
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
        $item = Item::with('piece')->leftJoin('categories','categories.id','items.category_id')->where('categories.is_pallet', 1)->selectRaw('items.*,categories.name as category');
        
        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html='<a ng-show="roleList.includes(\'operational_warehouse.pallet.master.edit\')" ng-click=\'edit('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-edit"></i></a>&nbsp;';
            $html.='<a ng-show="roleList.includes(\'operational_warehouse.pallet.master.delete\')" ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
            return $html;
        })
        ->addColumn('action_choose', function($item){
            $html='<a ng-click=\'choosePallet('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-check"></i></a>&nbsp;';
            return $html;
        })
        ->rawColumns(['action','action_choose'])
        ->make(true);
    }
    
    public function general_item_datatable(Request $request)
    {
        $item = Item::with('piece:id,name')
        ->leftJoin('categories','categories.id','items.category_id')
        ->leftJoin('categories as parents','parents.id','categories.parent_id')
        ->where('items.is_active', 1)
        ->selectRaw('items.*');

        if($request->is_pallet == 1) {
            $item = $item->where(function($query){
                $query->where('categories.is_pallet', 1);
                $query->orWhere('categories.is_pallet', 1);
            });
        }
        if($request->is_merchandise == 1) {
            $item = $item->where('items.is_merchandise', 1);
        }
        
        if($request->filled('purchase_order_id')) {
            $purchase_order_id = $request->purchase_order_id;
            $item = $item->whereRaw("items.id IN (SELECT item_id FROM purchase_order_details WHERE header_id = $purchase_order_id)");
        }

        if($request->filled('sales_order_return_id')) {
            $sales_order_return_id = $request->sales_order_return_id;
            $item = $item->whereRaw("items.id IN (SELECT item_id FROM sales_order_return_details WHERE header_id = $sales_order_return_id)");
        }


        if($request->filled('item_migration_id')) {
            $item_migration_id = $request->item_migration_id;
            $item = $item->whereRaw("items.id IN (SELECT item_id FROM item_migration_details WHERE header_id = $item_migration_id)");
        }

        return DataTables::of($item)
        ->addColumn('action_choose', function($item){
            $html='<a ng-click=\'choosePallet('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-check"></i></a>&nbsp;';
            return $html;
        })
        ->addColumn('action_choose_item', function($item){
            $html='<a ng-click=\'chooseItem('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-check"></i></a>&nbsp;';
            return $html;
        })
        ->rawColumns(['action_choose', 'action_choose_item'])
        
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
        $item = I::itemInWarehouseQuery($request->all());

        $item = $item->where('warehouse_stock_details.available_qty', '>', 0);
        
        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html='<a ng-click=\'edit('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-edit"></i></a>&nbsp;';
            $html.='<a ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
            return $html;
        })
        ->addColumn('action_choose', function($item){
            $html='<a ng-click=\'job_order.choosePallet('.json_encode($item,JSON_HEX_APOS).')\'><i class="fa fa-check"></i></a>&nbsp;';
            return $html;
        })
        ->rawColumns(['action','action_choose'])
        ->make(true);
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
            'warehouse_receipts.sender AS warehouse_receipt_sender',
            DB::raw("(SELECT IFNULL(SUM(qty), 0) FROM warehouse_stock_details WHERE warehouse_receipt_id = warehouse_receipts.id AND item_id = job_order_details.item_id AND rack_id = job_order_details.rack_id) AS stock")
        );
        
        return DataTables::of($item)
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
        ->leftJoin(DB::raw("(select group_concat(distinct ws.name separator '<br>') as warehouse, group_concat(DISTINCT receipt_lists.receive_date separator '<br>') as receive_date, receipt_lists.header_id from receipt_lists left join warehouses as ws on ws.id = receipt_lists.warehouse_id group by receipt_lists.header_id) rl"),'rl.header_id','receipts.id')
        ->selectRaw('
        receipts.*,
        companies.name as company,
        purchase_orders.code as po_code,
        purchase_orders.po_date as po_date,
        rl.warehouse as wr,
        rl.receive_date as receive_date
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
            $wr.=" and sales_order_returns.customer_id = $request->customer_id";
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
        ->leftJoin('sales_order_return_statuses','sales_order_return_statuses.id','sales_order_returns.status')
        ->leftJoin('contacts','contacts.id','sales_order_returns.customer_id')
        ->leftJoin('companies','companies.id','sales_order_returns.company_id')
        ->leftJoin(DB::raw('(select count(id) as jml, header_id from sales_order_return_details group by header_id) pod'),'pod.header_id','sales_order_returns.id')
        ->whereRaw($wr)
        ->selectRaw('
        sales_order_returns.*,
        sales_order_return_statuses.name AS status_name,
        contacts.name as customer,
        companies.name as company_name,
        pod.jml
        ');

        if($request->is_merchandise == 1) {
            $item = $item->whereRaw('sales_order_returns.id IN (SELECT sales_order_return_details.header_id FROM sales_order_return_details JOIN items ON items.id = sales_order_return_details.item_id WHERE items.is_merchandise = 1)');
        }

        if($request->is_pallet == 1) {
            $item = $item->whereRaw('sales_order_returns.id IN (SELECT sales_order_return_details.header_id FROM sales_order_return_details JOIN items ON items.id = sales_order_return_details.item_id WHERE items.id IN 1)');
        }
        
        return DataTables::of($item)
        ->addColumn('action_choose', function($item){
            $html='<button ng-click="chooseSoReturn('.$item->id.')" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Pilih</button>';
            return $html;
        })
        ->rawColumns(['action_choose'])
        ->make(true);
    }
    
    /*
    Date : 16-03-2020
    Description : Menampilkan daftar migrasi pallet dengan format 
    datatable
    Developer : Didin
    Status : Edit
    */
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
        ->leftJoin('racks as rfrom','rfrom.id','im.rack_from_id')
        ->leftJoin('racks as rto','rto.id','im.rack_to_id')
        ->whereRaw($wr)
        ->where('c.is_pallet', 1)
        ->selectRaw('
        im.*,
        wfrom.name as warehouse_from,
        wto.name as warehouse_to,
        rfrom.code as storage_from,
        rto.code as storage_to
        ');
        
        $item->orderBy('im.id', 'DESC');
        
        
        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('operational_warehouse.pallet.migration.detail')\" ui-sref='operational_warehouse.pallet_migration.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            if ($item->status==1) {
                $html.='<a ng-show="roleList.includes(\'operational_warehouse.pallet.migration.delete\')" ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
            }
            return $html;
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
        
        $item = DB::table('item_migrations as im')
        ->join('item_migration_details as imd','im.id','imd.header_id')
        ->join('items as i','i.id','imd.item_id')
        ->leftJoin('categories as c','i.category_id','c.id')
        ->leftJoin('warehouses as wfrom','wfrom.id','im.warehouse_from_id')
        ->leftJoin('warehouses as wto','wto.id','im.warehouse_to_id')
        ->leftJoin('racks as rfrom','rfrom.id','im.rack_from_id')
        ->leftJoin('racks as rto','rto.id','im.rack_to_id')
        ->whereRaw($wr);

        $type = ItemMigrationType::getItemMigration();
        if($type) {
            $item = $item->where('item_migration_type_id', $type);
        }
        
        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
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
        
        $item = $item->selectRaw('
        im.*,
        wfrom.name as warehouse_from,
        wto.name as warehouse_to,
        rfrom.code as storage_from,
        rto.code as storage_to
        ')->groupBy('im.id')->orderBy('date_transaction', 'desc')->orderBy('im.id', 'desc');

        if($request->is_pallet == 1) {
            $pallet = DB::table('items')
            ->join('categories', 'categories.id', 'items.category_id')
            ->join('categories AS parents', 'categories.parent_id', 'parents.id')
            ->whereRaw('categories.is_pallet = 1 OR parents.is_pallet = 1')
            ->select('items.id');

            $pallet = $pallet->toSql();

            $details = DB::table('item_migration_details')
            ->select('header_id')
            ->whereRaw("item_id IN ($pallet)");
            $details = $details->toSql();

            $item = $item->whereRaw("im.id IN ($details)");
        }
        
        return DataTables::of($item)
        ->editColumn('status_name', function($item){
            $stt=[
                1 => '<span class="badge badge-success">Pengajuan</span>',
                2 => '<span class="badge badge-primary">Item Out (On Transit)</span>',
                3 => '<span class="badge badge-info">Item Receipt (Done)</span>',
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['status_name'])
        ->make(true);
    }
    
    public function putaway_datatable(Request $request)
    {
        $wr="1=1";
        
        $item = DB::table('item_migrations as im')
        ->leftJoin('item_migration_statuses','item_migration_statuses.id','im.status')
        ->join('item_migration_details as imd','im.id','imd.header_id')
        ->join('items as i','i.id','imd.item_id')
        ->leftJoin('warehouses as wfrom','wfrom.id','im.warehouse_from_id')
        
        ->leftJoin('racks as rfrom','rfrom.id','im.rack_from_id')
        ->leftJoin('racks as rto','rto.id','im.rack_to_id')
        ->whereRaw($wr);
        
        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
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
        item_migration_statuses.name AS status_name,
        item_migration_statuses.slug AS status_slug,
        wfrom.name as warehouse_from,
        rfrom.code as storage_from,
        rto.code as storage_to
        ')->groupBy('im.id')->orderBy('date_transaction', 'desc')->orderBy('im.id', 'desc');
        
        
        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('inventory.putaway.detail')\" ui-sref='inventory.putaway.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            if ($item->status==1) {
                $html.='<a ng-show="roleList.includes(\'inventory.putaway.delete\')" ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
            }
            return $html;
        })
        ->editColumn('status_label', function($item){
            $stt=[
                1 => '<span class="badge badge-success">Pengajuan</span>',
                2 => '<span class="badge badge-primary">Item Out (On Transit)</span>',
                3 => '<span class="badge badge-info">Item Receipt (Done)</span>',
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['action','status_label'])
        ->make(true);
    }
    
     /*
      Date : 11-09-2020
      Description : Menampilkan stocklist
      Developer : Didin
      Status : Create
    */
    public function stocklist_datatable(Request $request)
    {
        $item = WSD::stocklist($request->all());
        
        return DataTables::of($item)
        ->editColumn('qty', function($item){
            return formatNumber($item->qty);
        })
        ->editColumn('warehouse_receipt.receive_date', function($item){
            return isset($item->warehouse_receipt) ? dateView($item->warehouse_receipt->receive_date) : null;
        })
        ->make(true);
    }
    public function picking_datatable(Request $request)
    {
        $wr="1=1";
        
        $item = Picking::with('warehouse', 'company');
        $item = $item->leftJoin('picking_statuses', 'picking_statuses.id', 'pickings.status');
        
        if($request->start_date) {
            $start = new \DateTime($request->start_date);
            $start_date = $start->format('Y-m-d');
            $item = $item->whereRaw("date_transaction >= '{$start_date}'");
        }
        
        if($request->end_date) {
            $end = new \DateTime($request->end_date);
            $end_date = $end->format('Y-m-d');
            $item = $item->whereRaw("date_transaction <= '{$end_date}'");
        }
        
        $warehouse_id = $request->warehouse_id;
        if($warehouse_id) {
            $item = $item->where('warehouse_id', $warehouse_id);
        }
        
        $status = $request->status;
        $status = $status != null ? $status : '';
        $item = $status != '' ? $item->where('status', $status) : $item;
        
        if ($request->company_id) {
            $item->where('pickings.company_id', $request->company_id);
        }
        
        $item = $item->select('pickings.*', 'picking_statuses.name AS status_name');
        $user = $request->user();


        return DataTables::of($item)
        ->addColumn('action', function($item) use ($user){
            $html='';
            
            if ($item->status==1 && $user->hasRole('inventory.picking.edit'))
            $html .= "<a ui-sref=\"operational_warehouse.picking.edit({id:$item->id})\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
            
            if ($user->hasRole('inventory.picking.detail'))
            $html .= "<a ui-sref='operational_warehouse.picking.show({id:$item->id})' ><span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";
            
            if ($item->status==1 && $user->hasRole('inventory.picking.delete'))
            $html.='<a ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
            
            return $html;
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => '<span class="badge badge-success">Pengajuan</span>',
                2 => '<span class="badge badge-primary">Disetujui </span>',
                3 => '<span class="badge badge-info">Selesai</span>',
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }
    public function pallet_stock_datatable(Request $request)
    {
        $wr="1=1";
        $wr.=" and categories.is_pallet = 1";
        $item = DB::table('warehouse_stocks')
        ->leftJoin('items','items.id','warehouse_stocks.item_id')
        ->leftJoin('categories','categories.id','items.category_id')
        ->leftJoin('warehouses','warehouses.id','warehouse_stocks.warehouse_id')
        ->leftJoin(DB::raw('( select pod.item_id,po.warehouse_id,ifnull(sum(pod.qty),0) as qty from purchase_order_details as pod left join purchase_orders as po on po.id = pod.header_id where po.po_status = 1 group by po.warehouse_id,pod.item_id) as po'), function ($join) {
            $join->on('po.warehouse_id','warehouse_stocks.warehouse_id');
            $join->on('po.item_id','warehouse_stocks.item_id');
        })
        ->whereRaw($wr)
        ->selectRaw('
        warehouse_stocks.*,
        warehouses.name as warehouse,
        items.name as item_name,
        categories.name as category,
        ifnull(po.qty,0) as qty_po
        ');
        
        return DataTables::of($item)
        ->make(true);
    }
    
    public function stok_opname_datatable(Request $request)
    {
        $wr="1=1";
        $wr.=" and categories.is_pallet = 1";
        $item = DB::table('stok_opname_warehouses');
        $item = $item->leftJoin('stok_opname_statuses', 'stok_opname_statuses.id', 'stok_opname_warehouses.status');
        $item = $item->leftJoin('warehouses', 'warehouses.id', 'stok_opname_warehouses.warehouse_id');
        $item = $item->leftJoin('companies', 'companies.id', 'warehouses.company_id');

        $item = $item->select(
            'stok_opname_warehouses.*', 
            'stok_opname_statuses.name AS status_name', 
            'stok_opname_statuses.slug AS status_slug',
            'warehouses.name AS warehouse_name',
            'companies.name AS company_name'
        );
        
        if(isset($request->company_id)){
            $item = $item->where('warehouses.company_id', $request->company_id);
        }
        if(isset($request->warehouse_id)){
            $item = $item->where('warehouse_id', $request->warehouse_id);
        }
        if(isset($request->status)){
            $item = $item->where('status', $request->status);
        }
        
        if ($request->start_date && $request->end_date) {
            $start = Carbon::parse($request->start_date)->format('Y-m-d');
            $end = Carbon::parse($request->end_date)->format('Y-m-d');
            $item = $item->whereRaw("DATE_FORMAT(stok_opname_warehouses.created_at, '%Y-%m-%d') BETWEEN $start AND $end");
        }
        
        return DataTables::of($item)
        ->addColumn('status_label', function($item){
            $stt=[
                1 => '<span class="badge badge-success">Pengajuan</span>',
                2 => '<span class="badge badge-primary">Disetujui</span>',
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['status_label'])
        ->make(true);
    }
    
    public function pallet_deletion_datatable(Request $request)
    {
        $wr="1=1";
        
        if ($request->warehouse_id) {
            $wr.=" and item_deletions.warehouse_id = $request->warehouse_id";
        }
        if ($request->start_date && $request->end_date) {
            $start=Carbon::parse($request->start_date)->format('Y-m-d');
            $end=Carbon::parse($request->end_date)->format('Y-m-d');
            $wr.=" and item_deletions.date_transaction between '$start' and '$end'";
        }
        
        $item = DB::table('item_deletions')
        ->leftJoin('item_deletion_statuses','item_deletion_statuses.id','item_deletions.status')
        ->leftJoin('warehouses','warehouses.id','item_deletions.warehouse_id')
        ->whereRaw($wr)
        ->selectRaw('
            item_deletions.*,
            item_deletion_statuses.name AS status_name,
            warehouses.name as warehouse_name
        ');
        
        return DataTables::of($item)
        ->make(true);
    }    
}
