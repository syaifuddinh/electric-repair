<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Model\Item;
use App\Model\StockInitial;
use App\Abstracts\PurchaseOrder;
use App\Model\Receipt;
use App\Model\StockAdjustment;
use App\Model\Warehouse;
use App\Model\UsingItem;
use App\Model\Retur;
use App\Abstracts\Inventory\ItemCondition;
use App\Abstracts\Inventory\WarehouseStock;
use App\Abstracts\Inventory\PurchaseRequest;
use DataTables;
use DB;
use Response;
use Carbon\Carbon;
use DateTime;

class InventoryApiController extends Controller
{
    public function item_condition_datatable(Request $request)
    {
        $item = ItemCondition::query();
        $item = $item->select('id', 'name', 'description');

        return DataTables::of($item)
        ->make(true);
    }

  public function picking_order_datatable(Request $request)
  {
    $item = DB::table('pickings as p');
    $item = $item->leftJoin('warehouses as w','w.id','p.warehouse_id');
    $item = $item->leftJoin('companies as c','c.id','p.company_id');
    $item = $item->leftJoin('contacts as cu','cu.id','p.customer_id');
    $item = $item->leftJoin('contacts as st','st.id','p.staff_id');
    $item = $item->selectRaw('p.*,w.name as warehouse,c.name as company,cu.name as customer,st.name as staff');
    return DataTables::of($item)->toJson();
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
        ->whereRaw($wr)
        ->where('is_inventory', 1);

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

        // $is_putaway = $request->is_putaway;
        // $is_putaway = $is_putaway != null ? $is_putaway : '';
        // $item = $is_putaway != '' ? $item->whereRaw('im.warehouse_from_id = im.warehouse_to_id') : $item;

        $warehouse_to = $request->warehouse_to;
        $warehouse_to = $warehouse_to != null ? $warehouse_to : '';
        $item = $warehouse_to != '' ? $item->where('im.warehouse_to_id', $warehouse_to) : $item;

        $item = $item->selectRaw('
            im.*,
            wfrom.name as warehouse_from,
            wto.name as warehouse_to,
            rfrom.code as storage_from,
            rto.code as storage_to
            ')->groupBy('im.id');

        if($request->draw == 1) {
            $item = $item->orderBy('date_transaction', 'desc')->orderBy('im.id', 'desc');
        }

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ui-sref='inventory.mutasi_transfer.show({id:$item->id})' ><span class='fa fa-folder-o'   title='Detail Data'></span></a>&nbsp;&nbsp;";
            if ($item->status==1) {
                $html.='<a ng-click="deletes('.$item->id.')"><i class="fa fa-trash"></i></a>';
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


    public function category_datatable(Request $request)
    {
        $item = Category::leftJoin('categories as parent','parent.id','=','categories.parent_id')->orderByRaw("COALESCE(categories.parent_id,categories.id), categories.parent_id IS NOT NULL, categories.id");

        if($request->default_rack_id) {
            $item = $item->where('categories.default_rack_id',$request->default_rack_id);
        }

        $item = $request->isAsset == 'true' ? $item->whereRaw('categories.is_asset = 1') : $item;
        $item = $request->isJasa == 'true' ? $item->whereRaw('categories.is_jasa = 1') : $item;
        $item = $item->select('categories.*','parent.name as pname');

    return DataTables::of($item)
      ->editColumn('code', function($item){
        if (empty($item->parent_id)) {
          $html="<b>$item->code</b>";
        } else {
          $html=$item->code;
        }
        return $html;
      })
      ->editColumn('name', function($item){
        if (empty($item->parent_id)) {
          $html="<b>$item->name</b>";
        } else {
          $html=$item->name;
        }
        return $html;
      })
      ->editColumn('is_asset', function($item){
        $stt=[
          1=>'V',
          0=>'-',
        ];
        return $stt[$item->is_asset];
      })
      ->editColumn('is_jasa', function($item){
        $stt=[
          1=>'V',
          0=>'-',
        ];
        return $stt[$item->is_jasa];
      })
      ->rawColumns(['is_jasa','code','name'])
      ->make(true);
  }
  
  public function stock_initial_datatable(Request $request)
  {
    $wr="1=1";
    if ($request->company_id) {
      $wr.=" and stock_initials.company_id = $request->company_id";
    }
    if ($request->warehouse_id) {
      $wr.=" and stock_initials.warehouse_id = $request->warehouse_id";
    }
    $item = StockInitial::with('warehouse:id,name','company:id,name','item','item.category')
        ->leftJoin('journals', 'journals.id', 'stock_initials.journal_id')
        ->whereRaw($wr)
        ->select('stock_initials.*', 'journals.status AS journal_status')
        ->orderByRaw('stock_initials.id DESC');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html = '';
        if($item->journal_status == 1) {
            $html.="<a ng-show=\"roleList.includes('inventory.first_stock.edit')\" ui-sref='inventory.stock_initial.edit({id:$item->id})' ><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        }
        $html.="<a ng-show=\"roleList.includes('inventory.first_stock.delete')\" ng-click='deletes($item->id)' ><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('price', function($item){
        return formatPrice($item->price);
      })
      ->editColumn('total', function($item){
        return formatPrice($item->total);
      })
      ->editColumn('qty', function($item){
        return formatNumber($item->qty);
      })
      ->rawColumns(['action'])
      ->make(true);
  }

    public function purchase_request_datatable(Request $request)
    {
        $item = PurchaseRequest::query($request->all());

        $item = $item->select('purchase_requests.id', 'companies.name AS company_name', 'suppliers.name AS supplier_name', 'purchase_requests.code', 'purchase_requests.date_needed',  'purchase_requests.date_request', 'purchase_requests.status');

        return DataTables::of($item)
        ->editColumn('status_label', function($item){
            $stt=[
              0 => '<span class="label label-danger">Ditolak</span>',
              1 => '<span class="label label-warning">Belum Persetujuan</span>',
              2 => '<span class="label label-primary">Sudah Persetujuan</span>',
              3 => '<span class="label label-success">Purchase Order</span>',
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['status_label'])
        ->make(true);
    }

    public function pallet_purchase_order_datatable(Request $request) {
        $request->is_pallet = 1;
        return $this->purchase_order_datatable($request);
    }

    public function purchase_order_datatable(Request $request)
    {
        $item = PurchaseOrder::query($request->all());

        return DataTables::of($item)
          ->addColumn('action_choose', function($item){
            $html='<button ng-click="choosePO('.$item->id.',\''.$item->code.'\')" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Pilih</button>';
            return $html;
          })
          ->rawColumns(['action_choose'])
          ->make(true);
    }

  public function adjustment_datatable(Request $request)
  {
    $item = StockAdjustment::with('company:id,name','warehouse:id,name','creates:id,name');

    $start_date = $request->start_date;
    $start_date = $start_date != null ? new DateTime($start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? new DateTime($end_date) : '';
    $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_transaction', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

    $company_id = $request->company_id;
    $company_id = $company_id != null ? $company_id : '';
    $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;

    $warehouse_id = $request->warehouse_id;
    $warehouse_id = $warehouse_id != null ? $warehouse_id : '';
    $item = $warehouse_id != '' ? $item->where('warehouse_id', $warehouse_id) : $item;


    $item->select('stock_adjustments.*')
        ->orderByRaw('stock_adjustments.id DESC');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('inventory.adjustment.detail')\" ui-sref='inventory.adjustment.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

    public function warehouse_stock_datatable(Request $request)
    {
        $item = WarehouseStock::query($request->all());
        $item = $item->select('companies.name AS company_name', 'warehouses.name AS warehouse_name', 'items.name AS item_name', 'categories.name AS category_name', 'items.id AS item_id AS id', 'warehouse_stocks.qty');

        return DataTables::of($item)
          ->make(true);
    }

    public function stock_by_item_datatable(Request $request)
    {
        $params = $request->all();
        $params['group_by_item'] = 1;
        $item = WarehouseStock::query($params);
        $item = $item->select('companies.name AS company_name', 'items.name AS item_name', 'categories.name AS category_name', 'items.id AS item_id AS id', 'warehouse_stocks.qty');

        return DataTables::of($item)
          ->make(true);
    }

    public function using_item_datatable(Request $request)
    {
        $item = UsingItem::with('company','vehicle');
        $item = $item->leftJoin('using_item_statuses', 'using_items.status', 'using_item_statuses.id');

        $start_date = $request->start_date;
        $start_date = $start_date != null ? Carbon::parse($start_date)->format("Y-m-d") : null;
        $end_date = $request->end_date;
        $end_date = $end_date != null ? Carbon::parse($end_date)->format("Y-m-d") : null;
        if($start_date) {
            $item = $item->where("date_request", ">=", $start_date);
        }
        if($end_date) {
            $item = $item->where("date_request", "<=", $end_date);
        }

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

        $item->select('using_items.*', 'using_item_statuses.name AS status_name')
            ->orderByRaw('using_items.id DESC');

        if($request->is_pallet == 1) {
            $pallet = DB::table('items')
            ->join('categories', 'categories.id', 'items.category_id')
            ->join('categories AS parents', 'categories.parent_id', 'parents.id')
            ->whereRaw('categories.is_pallet = 1 OR parents.is_pallet = 1')
            ->select('items.id');

            $pallet = $pallet->toSql();

            $details = DB::table('using_item_details')
            ->select('header_id')
            ->whereRaw("item_id IN ($pallet)");
            $details = $details->toSql();

            $item = $item->whereRaw("using_items.id IN ($details)");
        }

        return DataTables::of($item)
          ->make(true);
    }

  public function stock_transaction_datatable(Request $request)
  {
    $dt = DB::table('stock_transactions as st');
    $dt = $dt->leftJoin('type_transactions as tp','tp.id','st.type_transaction_id');
    $dt = $dt->selectRaw('st.code,st.date_transaction,st.description,st.qty_masuk,st.qty_keluar,tp.name as type_transactions');
    if ($request->filled('warehouse_id')) {
      $dt = $dt->where('st.warehouse_id', $request->warehouse_id);
    }
    if ($request->filled('item_id')) {
      $dt = $dt->where('st.item_id', $request->item_id);
    }
    $dt = $dt->get();
    return response()->json($dt);

    $wr="WHERE 1=1";

    $start_qty_masuk = $request->start_qty_masuk;
    $start_qty_masuk = $start_qty_masuk != null ? $start_qty_masuk : 0;
    $end_qty_masuk = $request->end_qty_masuk;
    $end_qty_masuk = $end_qty_masuk != null ? $end_qty_masuk : 0;
    $wr .= $end_qty_masuk != 0 ? " AND qty_masuk BETWEEN $start_qty_masuk AND $end_qty_masuk" : '';

    $start_qty_keluar = $request->start_qty_keluar;
    $start_qty_keluar = $start_qty_keluar != null ? $start_qty_keluar : 0;
    $end_qty_keluar = $request->end_qty_keluar;
    $end_qty_keluar = $end_qty_keluar != null ? $end_qty_keluar : 0;
    $wr .= $end_qty_keluar != 0 ? " AND qty_keluar BETWEEN $start_qty_keluar AND $end_qty_keluar" : '';

    $start_date = $request->start_date;
    $start_date = $start_date != null ? new DateTime($start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? new DateTime($end_date) : '';
    $wr .= $start_date != '' && $end_date != '' ? " AND date_transaction BETWEEN '" . $start_date->format('Y-m-d') . "' AND '" . $end_date->format('Y-m-d') . "'" : '';


    DB::statement(DB::raw("set @balance = 0"));
    $sql = "select type_transactions.name as type_trx_name, date_transaction, code, description, qty_masuk, qty_keluar, (@balance := @balance + (qty_masuk - qty_keluar)) as total from stock_transactions left join type_transactions on type_transactions.id = stock_transactions.type_transaction_id ".$wr." ORDER BY stock_transactions.date_transaction desc;";
    $items = DB::select(DB::raw($sql));
    $item = collect($items);


    return DataTables::of($item)
      ->editColumn('total', function($item){
        return formatNumber($item->total);
      })
      ->editColumn('qty_masuk', function($item){
        return formatNumber($item->qty_masuk);
      })
      ->editColumn('qty_keluar', function($item){
        return formatNumber($item->qty_keluar);
      })
      ->make(true);
  }

  public function receipt_datatable(Request $request)
  {
    $item = Receipt::with('company','purchase_order','lists','lists.warehouse');

    $start_date = $request->start_date;
    $start_date = $start_date != null ? new DateTime($start_date) : '';
    $end_date = $request->end_date;
    $end_date = $end_date != null ? new DateTime($end_date) : '';
    $item = $start_date != '' && $end_date != '' ? $item->whereHas("lists", function($q) use($start_date, $end_date){
                  $q->whereBetween('receive_date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
            }) : $item;

    $company_id = $request->company_id;
    $company_id = $company_id != null ? $company_id : '';
    $item = $company_id != '' ? $item->where(DB::raw('receipts.company_id'), $company_id) : $item;

    $warehouse_id = $request->warehouse_id;
    $warehouse_id = $warehouse_id != null ? $warehouse_id : '';
    $item = $warehouse_id != '' ? $item->whereHas("lists.warehouse", function($q) use($warehouse_id){
                  $q->where('warehouse_id', $warehouse_id);
            }) : $item;

    $status = $request->status;
    $status = $status != null ? $status : '';
    $item = $status != '' ? $item->where(DB::raw('receipts.status'), $status) : $item;

    $item->select('receipts.*')
        ->orderByRaw('receipts.id DESC');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('inventory.receipt.detail')\" ui-sref='inventory.receipt.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->editColumn('po_date', function($item){
        return dateView($item->po_date);
      })
      ->addColumn('receive_date', function(Receipt $item){
        return $item->lists->map(function($list){
          return '- '.dateView($list->receive_date);
        })->implode('<br>');
      })
      ->addColumn('gudang_name', function(Receipt $item){
        return $item->lists->map(function($list){
          return '- '.$list->warehouse->name;
        })->implode('<br>');
      })
      ->editColumn('status', function($item){
        $stt=[
          1 => '<span class="badge badge-warning">Belum Lengkap</span>',
          2 => '<span class="badge badge-success">Sudah Lengkap</span>',
        ];
        return $stt[$item->status];
      })
      ->rawColumns(['action','status','gudang_name','receive_date'])
      ->make(true);
  }

    public function gudang_dan_item()
    {
        $data['warehouse']=DB::table('warehouses')->selectRaw('id,name')->get();
        $data['item']=DB::table('items')->selectRaw('id,code,name')->get();
        return Response::json($data, 200);
    }

    public function retur_datatable(Request $request)
    {
        $item = Retur::with('company','receipt_list','receipt_list.receipt','supplier');

        $item = $item->join('warehouses', 'warehouses.id', 'returs.warehouse_id');

        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        if($start_date) {
            $start_date = $start_date->format('Y-m-d');
            $item = $item->where('returs.date_transaction', '>=', $start_date);
        }

        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        if($end_date) {
            $end_date = $end_date->format('Y-m-d');
            $item = $item->where('returs.date_transaction', '<=', $end_date);
        }

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where('returs.company_id', $company_id) : $item;

        $supplier_id = $request->supplier_id;
        $supplier_id = $supplier_id != null ? $supplier_id : '';
        $item = $supplier_id != '' ? $item->where('supplier_id', $supplier_id) : $item;

        $status = $request->status;
        $status = $status != null ? $status : '';
        $item = $status != '' ? $item->where('status', $status) : $item;


        $item->select('returs.*', 'warehouses.name AS warehouse_name')
            ->orderByRaw('returs.id DESC');

        if($request->is_pallet == 1) {
            $pallet = DB::table('items')
            ->join('categories', 'categories.id', 'items.category_id')
            ->join('categories AS parents', 'categories.parent_id', 'parents.id')
            ->whereRaw('categories.is_pallet = 1 OR parents.is_pallet = 1')
            ->select('items.id');

            $pallet = $pallet->toSql();

            $details = DB::table('retur_details')
            ->select('header_id')
            ->whereRaw("item_id IN ($pallet)");
            $details = $details->toSql();

            $item = $item->whereRaw("returs.id IN ($details)");
        }

        if($request->is_merchandise == 1) {
            $item = $item->whereRaw('returs.id IN (SELECT retur_details.header_id FROM retur_details JOIN items ON items.id = retur_details.item_id WHERE items.is_merchandise = 1)');
        }

    return DataTables::of($item)
      ->editColumn('status', function($item){
        $stt=[
          1 => '<span class="badge badge-warning">Draft</span>',
          2 => '<span class="badge badge-info">Approve</span>'
        ];
        
        return $stt[$item->status];
      })
      ->rawColumns(['status'])
      ->make(true);
  }
  public function warehouse_datatable()
  {
    $item = Warehouse::with('company')->select('warehouses.*');

    return DataTables::of($item)
      // ->addColumn('action', function($item){
      //   $html="<a ui-sref='inventory.retur.show({id:$item->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
      //   return $html;
      // })
      ->editColumn('capacity_volume', function($item){
        return formatNumber($item->capacity_volume).' m3';
      })
      ->editColumn('capacity_tonase', function($item){
        return formatNumber($item->capacity_tonase).' kg';
      })
      // ->rawColumns(['action','status'])
      ->make(true);
  }

}
