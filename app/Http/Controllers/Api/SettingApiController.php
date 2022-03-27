<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use App\Model\Company;
use App\Model\Vessel;
use App\Model\Piece;
use App\Model\Commodity;
use App\Model\Service;
use App\Model\ContainerType;
use App\Model\GroupType;
use App\Model\RouteCost;
use App\Model\Account;
use App\Model\Tax;
use App\Model\CashCategory;
use App\Model\City;
use App\Model\Bank;
use App\Model\Port;
use App\Model\AirPort;
use App\Model\CustomerStage;
use App\Model\CostType;
use App\Model\Journal;
use App\Model\Payable;
use App\Model\Receivable;
use App\Model\UmSupplier;
use App\Model\UmCustomer;
use App\Model\JournalFavorite;
use App\Model\RouteCostDetail;
use App\Model\VehicleJoint;
use App\Model\VehicleVariant;
use App\Model\VendorType;
use App\Model\AddressType;
use App\Model\VehicleMaintenanceType;
use App\Model\VehicleChecklist;
use App\Model\VehicleBody;
use App\Model\VehicleType;
use App\Model\VehicleManufacturer;
use App\Model\VehicleOwner;
use App\Model\LeadStatus;
use App\Model\LeadSource;
use App\Model\Industry;
use App\Model\Route as Trayek;
use App\User;
use DataTables;
use DB;

class SettingApiController extends Controller
{
    public function additional_field_datatable(Request $request) {
        $dt = \App\Abstracts\AdditionalField::query();
        $dt->select('additional_fields.id', 'additional_fields.name', 'type_transactions.name AS type_transaction_name', 'field_types.name AS field_type_name');

        return DataTables::query($dt)->make(true);
    }  

  public function company_datatable(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND companies.id = ".auth()->user()->company_id;
    }
    $item = Company::with('area')
        ->whereRaw($wr)
        ->when(!$request->order, function ($query) {
            $query->orderByDesc('created_at');
        })->select('companies.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.company.detail')\" ui-sref=\"setting.company.show.info({id:$item->id})\" data-toggle='tooltip' title='Show Detail Cabang'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.company.edit')\" ui-sref=\"setting.company.edit({id:$item->id})\" data-toggle='tooltip' title='Edit Data Cabang'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.company.delete')\" ng-click='deletes($item->id)' data-toggle='tooltip' title='Delete Data Cabang'><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  /*
      Date : 03-04-2020
      Description : Menampilkan widget dalam format datatable 
      Developer : Didin
      Status : Create
  */
  public function widget_datatable()
  {
    $item = DB::table('widgets AS W')
    ->join('queries AS Q', 'Q.id', 'W.query_id')
    ->select('W.id', 'W.name', 'W.type', 'W.width', 'Q.name AS query_name');

    return DataTables::of($item)
      ->make(true);
  }

  /*
      Date : 03-04-2020
      Description : Menampilkan dashboard layout dalam format datatable 
      Developer : Didin
      Status : Create
  */

  public function dashboard_datatable()
  {
    $item = DB::table('dashboards AS D')
    ->select('D.id', 'D.name');

    return DataTables::of($item)
      ->make(true);
  }

  public function user_datatable(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND company_id = ".auth()->user()->company_id;
    }
    $item = User::with('company')
        //->whereRaw($wr.' and contact_id is null')
        ->select('users.*');

    return DataTables::of($item)
      ->addColumn('action', function($item) use ($request){
        $html="<a ng-show=\"roleList.includes('setting.user_management.detail')\" ui-sref=\"setting.user.show.personal({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.user_management.edit')\" ui-sref=\"setting.user.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        if ($request->user_now!=$item->id) {
          $html.="<a ng-show=\"roleList.includes('setting.user_management.hapus')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function group_datatable()
  {
    $item = GroupType::with('users')->get();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.user_management.group.privilage')\" ui-sref=\"setting.user.group.previlage({id:$item->id})\"><span class='fa fa-gears'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.user_management.group.edit')\" ng-click=\"edits($item->id,'$item->name')\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        if(count($item->users) <= 0)
          $html.="<a ng-show=\"roleList.includes('setting.user_management.group.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function vessel_datatable(Request $request)
  {
    $item = Vessel::with('vendor')
        ->select('vessels.*')
        ->when(!$request->order, function ($query) {
            $query->orderByDesc('created_at');
        });

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.vessel.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.vessel.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function countries_datatable(Request $request)
  {
    $item = DB::table('countries')
        ->when(!$request->order, function ($query) {
            $query->orderByDesc('created_at');
        });

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-click='edit(\$event.currentTarget)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-click=\"delete($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function bank_datatable(Request $request)
  {
    $item = Bank::query()
        ->when(!$request->order, function ($query) {
            $query->orderByDesc('created_at');
        });

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.bank.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.bank.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function lead_status_datatable()
  {
    $item = LeadStatus::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.lead.status_lead.edit')\" ng-click='editStatus($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->editColumn('is_active', function($item){
        $std = [
          1 => '<span class="badge badge-success">YA</span>',
          0 => '<span class="badge badge-danger">TIDAK</span>',
        ];
        return $std[$item->is_active];
      })
      ->rawColumns(['action','is_active'])
      ->make(true);
  }
  public function lead_source_datatable()
  {
    $item = LeadSource::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.lead.source_lead.edit')\" ng-click='editSource($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->editColumn('is_active', function($item){
        $std = [
          1 => '<span class="badge badge-success">YA</span>',
          0 => '<span class="badge badge-danger">TIDAK</span>',
        ];
        return $std[$item->is_active];
      })
      ->rawColumns(['action','is_active'])
      ->make(true);
  }
  public function industry_datatable()
  {
    $item = Industry::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.lead.industries.edit')\" ng-click='editIndustry($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->editColumn('is_active', function($item){
        $std = [
          1 => '<span class="badge badge-success">YA</span>',
          0 => '<span class="badge badge-danger">TIDAK</span>',
        ];
        return $std[$item->is_active];
      })
      ->rawColumns(['action','is_active'])
      ->make(true);
  }
  public function port_datatable()
  {
    $item = Port::leftJoin('countries','countries.id','=','ports.country_id')->select("ports.*",DB::raw("CONCAT(ports.name,', ',island_name,', ',countries.name) as portname"));

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.dermagabandara.dermaga.edit')\" ng-click='editPort($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.dermagabandara.dermaga.delete')\" ng-click=\"deletePort($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->filterColumn('portname', function($query, $keyword) {
        $sql = "CONCAT(ports.name,', ',island_name,', ',countries.name) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function airport_datatable()
  {
    $item = AirPort::select("air_ports.*",DB::raw("CONCAT(name,' (',code,')') as portname"));

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.dermagabandara.bandara.edit')\" ng-click='editAirPort($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.dermagabandara.bandara.delete')\" ng-click=\"deleteAirPort($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->filterColumn('portname', function($query, $keyword) {
        $sql = "CONCAT(name,', ',island_name) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function customer_stage_datatable()
  {
    $item = CustomerStage::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.customer_stage.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.customer_stage.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('is_close_deal', function($item){
        $std = [
          1 => '<span class="badge badge-success">YA</span>',
          0 => '<span class="badge badge-danger">TIDAK</span>',
        ];
        return $std[$item->is_close_deal];
      })
      ->editColumn('is_prospect', function($item){
        $std = [
          1 => '<span class="badge badge-success">YA</span>',
          0 => '<span class="badge badge-danger">TIDAK</span>',
        ];
        return $std[$item->is_prospect];
      })
      ->editColumn('is_negotiation', function($item){
        $std = [
          1 => '<span class="badge badge-success">YA</span>',
          0 => '<span class="badge badge-danger">TIDAK</span>',
        ];
        return $std[$item->is_negotiation];
      })
      ->rawColumns(['action','is_close_deal','is_negotiation','is_prospect'])
      ->make(true);
  }
  public function satuan_datatable()
  {
    $item = Piece::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.piece.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.piece.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function commodity_datatable()
  {
    $item = Commodity::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="";
        if ($item->id!=1) {
          $html.="<a ng-show=\"roleList.includes('setting.general_setting.commodity.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('setting.general_setting.commodity.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->editColumn('is_expired', function($item){
        $std = [
          1 => 'Ya',
          0 => 'Tidak',
        ];
        return $std[$item->is_expired];
      })

      ->rawColumns(['action'])
      ->make(true);
  }

  public function service_datatable()
  {
    $item = Service::with('service_type','service_group')->leftJoin('accounts as account_sale','account_sale.id','=','services.account_sale_id')->where('is_warehouse', 0)->select('services.*',DB::raw("CONCAT(account_sale.code,' - ',account_sale.name) as account_name"));

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.service.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.service.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('is_default', function($item){
        $std = [
          1 => 'Ya',
          0 => 'Tidak',
        ];
        return $std[$item->is_default];
      })
      ->filterColumn('account_name', function($query, $keyword) {
          $sql = "CONCAT(account_sale.code,' - ',account_sale.name) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
          })
      ->rawColumns(['action'])
      ->make(true);
  }


  public function vendor_job_status_datatable(Request $request)
  {
    $item = DB::table('vendor_job_statuses');

    return DataTables::of($item)
    ->make(true);
  }

  public function service_warehouse_datatable()
  {
    $item = Service::with('service_type','service_group')->leftJoin('accounts as account_sale','account_sale.id','=','services.account_sale_id')->where('is_warehouse', 1)->select('services.*',DB::raw("CONCAT(account_sale.code,' - ',account_sale.name) as account_name"));

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.service.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";

        return $html;
      })
      ->editColumn('is_default', function($item){
        $std = [
          1 => 'Ya',
          0 => 'Tidak',
        ];
        return $std[$item->is_default];
      })
      ->filterColumn('account_name', function($query, $keyword) {
          $sql = "CONCAT(account_sale.code,' - ',account_sale.name) like ?";
          $query->whereRaw($sql, ["%{$keyword}%"]);
          })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function container_datatable()
  {
    $item = ContainerType::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.container_type.edit')\" ng-click='edit(\$event.currentTarget)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.container_type.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function account_datatable()
  {
    $sql = "
      SELECT
      	accounts.id,
      	accounts.is_base,
      	accounts.deep,
      	accounts.code,
      	accounts.name,
      IF
      	( accounts.jenis = 1, 'DEBET', 'KREDIT' ) AS jenis,
      IF
      	( group_report = 1, 'NERACA', 'LABA - RUGI' ) as group_report,
      	account_types.name AS type
      FROM
      	accounts
      	LEFT JOIN account_types ON account_types.id = accounts.type_id
      ORDER BY
      CODE ASC
      ";
    $item = DB::select($sql);
    // $item = Account::leftJoin('accounts as parent','parent.id','=','accounts.parent_id')->orderByRaw("COALESCE(accounts.parent_id,accounts.id), accounts.parent_id IS NOT NULL, accounts.id")->select('accounts.*','parent.name as pname');


    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.finance.account.edit')\" ui-sref=\"setting.account.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.finance.account.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('name', function($item){
        $html="";
        for ($i=0; $i < $item->deep; $i++) {
          $html.="&nbsp;&nbsp;";
        }
        if ($item->is_base==1) {
          $html.="<b>$item->name</b>";
        } else {
          $html.=$item->name;
        }
        return $html;
      })
      ->rawColumns(['action','name'])
      ->make(true);
  }

  public function tax_datatable()
  {
    $item = Tax::leftJoin('accounts as akun1','akun1.id','=','taxes.akun_pembelian')
            ->leftJoin('accounts as akun2','akun2.id','=','taxes.akun_penjualan')
            ->select('taxes.*','akun1.name as akun_pembelian','akun2.name as akun_penjualan');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.finance.tax.edit')\" ui-sref=\"setting.tax.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.finance.tax.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  /*
      Date : 24-07-2020
      Description : Menampilkan daftar kota dalam format datatable
      Developer : Didin
      Status : Edit
  */
  public function city_datatable(request $request)
  {
    $item = city::with('province:id,name,country_id','province.country:id,name')->select('cities.*');

    return datatables::of($item)
      ->addcolumn('action', function($item){
        $html="<a ng-show=\"rolelist.includes('setting.delivery.region.edit')\" ui-sref=\"setting.city.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='edit data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-click='delete({$item->id})'><span class='fa fa-trash' data-toggle='tooltip' title='hapus data'></span></a>&nbsp;&nbsp;";
        return $html;
      })
      ->rawcolumns(['action'])
      ->make(true);
  }

  public function province_datatable(Request $request)
  {
    $item = DB::table('provinces')
    ->join('countries', 'countries.id', 'provinces.country_id')
    ->select('provinces.id', 'provinces.name', 'countries.name AS country_name');

    return DataTables::query($item)
      ->make(true);
  }

  public function cost_type_datatable()
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND cost_types.company_id = ".auth()->user()->company_id;
    }

    $item = CostType::with('vendor','company')->whereRaw($wr)->orderBy('code','asc')->select('cost_types.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.operational.cost_type.edit')\" ui-sref=\"setting.cost_type.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        if (isset($item->parent_id)) {
          $html.="<a ng-show=\"roleList.includes('setting.operational.cost_type.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->editColumn('name', function($item){
        if (empty($item->parent_id)) {
          $html="<strong>$item->name</strong>";
        } else {
          $html="&nbsp;&nbsp;".$item->name;
        }
        return $html;
      })
      ->editColumn('initial_cost', function($item){
        return formatPrice($item->initial_cost);
      })
      ->rawColumns(['action','name'])
      ->make(true);
  }

  public function cash_category_datatable()
  {
    $item = CashCategory::leftJoin('cash_categories as parent','parent.id','=','cash_categories.parent_id')
            ->select('cash_categories.*','parent.name as kategori_kas');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.finance.cash_category.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        if ($item->is_base==0) {
          $html.="<a ng-show=\"roleList.includes('setting.finance.cash_category.detail')\" ui-sref=\"setting.cash_category.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('setting.finance.cash_category.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->editColumn('name', function($item){
        $html="";
        if ($item->is_base==1) {
          $html.="<b>$item->name</b>";
        } else {
          $html.=$item->name;
        }
        return $html;
      })
      ->rawColumns(['action','name'])
      ->make(true);
  }
  public function saldo_account_datatable()
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND journals.company_id = ".auth()->user()->company_id;
    }

    $item = Journal::leftJoin('type_transactions','type_transactions.id','=','journals.type_transaction_id')
            ->where('type_transactions.slug','saldoAwal')
            ->whereRaw($wr)
            ->select('journals.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.finance.saldo.account.detail')\" ui-sref=\"setting.saldo_account.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->status==1) {
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.account.edit')\" ui-sref=\"finance.journal.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.account.hapus')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->editColumn('date_transaction', function($item){
        return dateView($item->date_transaction);
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function saldo_payable_datatable(Request $request)
  {
    // dd($request->not_in_id);
    $wr="1=1";
    if (isset($request->contact_id)) {
      $wr.=" AND payables.contact_id = $request->contact_id";
    }
    if (isset($request->exclude_zero)) {
      $wr.=" AND (payables.credit-payables.debet) > 0";
    }
    if (isset($request->no_invoice)) {
      $wr.=" AND is_invoice = 0";
    }
    if (isset($request->not_in_id)) {
      $arr=$request->not_in_id;
      $wr.=' and payables.id not in (';
      foreach ($request->not_in_id as $key => $value) {
        if (empty($value)) {
          continue;
        }
        if (end($arr)==$value) {
          $wr.="'".$value['id']."'";
        } else {
          $wr.="'".$value['id']."'".',';
        }
      }
      $wr.=")";
    }
    // if (auth()->user()->is_admin==0) {
    //   $wr.=" AND payables.company_id = ".auth()->user()->company_id;
    // }

    $item = Payable::leftJoin('type_transactions','type_transactions.id','=','payables.type_transaction_id', 'journal:id,status')
            ->leftJoin('contacts','contacts.id','=','payables.contact_id')
            ->leftJoin('companies','companies.id','=','payables.company_id')
            // ->where('type_transactions.slug','saldoAwal')
            ->whereRaw($wr)
            ->whereHas('journal', function(Builder $query){
                $query->whereStatus(3);
            })
            ->select('payables.*','contacts.name as cname', 'type_transactions.name as type_trans','companies.name as coname',DB::raw("(credit-debet) as total"),DB::raw("DATEDIFF(payables.date_transaction,payables.date_tempo) as umur"))
            ->orderBy('payables.id', 'desc');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.finance.saldo.credit.detail')\" ui-sref=\"setting.saldo_payable.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        // if ($item->journal->status<2) {
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.credit.edit')\" ui-sref=\"setting.saldo_payable.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.credit.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        // }
        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html="<button ng-click='choosePayable(".json_encode($item).")' class='btn btn-xs btn-success'><i class='fa fa-check'></i> Pilih</button>";
        return $html;
      })
      ->filterColumn('umur', function($query, $keyword) {
        $sql = "DATEDIFF(payables.date_transaction,payables.date_tempo) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->filterColumn('total', function($query, $keyword) {
        $sql = "(payables.credit-payables.debet) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->editColumn('date_transaction', function($item){
        return dateView($item->date_transaction);
      })
      ->editColumn('total', function($item){
        return formatPrice($item->total);
      })
      ->rawColumns(['action','action_choose'])
      ->make(true);
  }
  public function saldo_receivable_datatable(Request $request)
  {
    $wr="1=1";
    if (isset($request->customer_id)) {
      $wr.=" AND receivables.contact_id = $request->customer_id";
    }
    if (isset($request->exclude_zero)) {
      $wr.=" AND (receivables.debet-receivables.credit) > 0";
    }
    if (empty($request->is_receivable)) {
      $wr.=" AND type_transactions.slug = 'saldoAwal'";
    }
    if ($request->company_id) {
      $wr.=" AND receivables.company_id = ".$request->company_id;
    }
    if (isset($request->exclude_receivable)) {
      $wr.=" AND receivables.id not in ($request->exclude_receivable)";
    }
    if ($request->is_posting) {
      $wr.=" AND journals.status = 3";
    }
    $keyword = $request->search['value'];
    $keyword = $keyword != null ? $keyword : null;
    $item = Receivable::leftJoin('type_transactions','type_transactions.id','=','receivables.type_transaction_id')
            ->leftJoin('contacts','contacts.id','=','receivables.contact_id')
            ->leftJoin('companies','companies.id','=','receivables.company_id')
            ->leftJoin('journals','journals.id','=','receivables.journal_id')
            // ->where('type_transactions.slug','saldoAwal')
            ->whereRaw($wr);

    $item = $keyword != '' ? $item->whereRaw("(receivables.code LIKE '%$keyword%' OR receivables.description LIKE '%$keyword%')") : $item;
    $item = $item->select('receivables.*','contacts.name as cname','type_transactions.name as type_trans','companies.name as coname',DB::raw("(receivables.debet-receivables.credit) as total"),DB::raw("DATEDIFF(DATE_FORMAT(NOW(), '%Y-%m-%d'),receivables.date_tempo) as umur"));
    if($request->draw == 1) {

      $item->orderByRaw('receivables.date_transaction desc');
    }

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.finance.saldo.debt.detail')\" ui-sref=\"setting.saldo_receivable.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->journal->status<2) {
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.debt.edit')\" ui-sref=\"setting.saldo_receivable.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.debt.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->addColumn('action_choose', function($item){
        $html='<button ng-click="chooseReceivable('.$item->id.',\''.$item->code.'\','.($item->debet-$item->credit).')" class="btn btn-xs btn-success"><i class="fa fa-check"></i> Pilih</button>';
        return $html;
      })
      ->filterColumn('umur', function($query, $keyword) {
        $sql = "DATEDIFF(DATE_FORMAT(NOW(), '%Y-%m-%d'),receivables.date_tempo) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->filterColumn('total', function($query, $keyword) {
        $sql = "(receivables.debet-receivables.credit) like ?";
        $query->whereRaw($sql, ["%{$keyword}%"]);
        })
      ->editColumn('debet', function($item){
        return formatPrice($item->debet);
      })
      ->editColumn('total', function($item){
        return formatPrice($item->total);
      })
      ->rawColumns(['action','action_choose'])
      ->make(true);
  }
  public function saldo_um_supplier_datatable()
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND um_suppliers.company_id = ".auth()->user()->company_id;
    }
    $item = UmSupplier::leftJoin('type_transactions','type_transactions.id','=','um_suppliers.type_transaction_id')
            ->leftJoin('contacts','contacts.id','=','um_suppliers.contact_id')
            ->leftJoin('companies','companies.id','=','um_suppliers.company_id')
            ->where('type_transactions.slug','saldoAwal')
            ->whereRaw($wr)
            ->select('um_suppliers.*','contacts.name as cname','companies.name as coname');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.finance.saldo.deposit_supplier.detail')\" ui-sref=\"setting.saldo_um_supplier.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->journal->status<2) {
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.deposit_supplier.edit')\" ui-sref=\"setting.saldo_um_supplier.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.deposit_supplier.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->editColumn('date_transaction', function($item){
        return dateView($item->date_transaction);
      })
      ->editColumn('debet', function($item){
        return formatPrice($item->debet);
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function saldo_um_customer_datatable()
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND um_customers.company_id = ".auth()->user()->company_id;
    }
    $item = UmCustomer::leftJoin('type_transactions','type_transactions.id','=','um_customers.type_transaction_id')
            ->leftJoin('contacts','contacts.id','=','um_customers.contact_id')
            ->leftJoin('companies','companies.id','=','um_customers.company_id')
            ->where('type_transactions.slug','saldoAwal')
            ->whereRaw($wr)
            ->select('um_customers.*','contacts.name as cname','companies.name as coname');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.finance.saldo.deposit_customer.detail')\" ui-sref=\"setting.saldo_um_customer.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        if ($item->journal->status<2) {
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.deposit_customer.edit')\" ui-sref=\"setting.saldo_um_customer.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
          $html.="<a ng-show=\"roleList.includes('setting.finance.saldo.deposit_customer.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        }
        return $html;
      })
      ->editColumn('date_transaction', function($item){
        return dateView($item->date_transaction);
      })
      ->editColumn('credit', function($item){
        return formatPrice($item->credit);
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function favorite_datatable()
  {
    $item = JournalFavorite::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.finance.favorite_transaction.edit')\" ui-sref=\"setting.favorite.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        // $html.="<a ui-sref=\"setting.favorite.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.finance.favorite_transaction.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function route_datatable(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND routes.company_id = ".auth()->user()->company_id;
    }
    $item = Trayek::with('company','from','to')->whereRaw($wr)->select('routes.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.delivery.route.edit')\" ui-sref=\"setting.route.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.delivery.route.detail')\" ui-sref=\"setting.route.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.delivery.route.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('duration', function($item){
        $type=[
          1 => 'Jam',
          2 => 'Hari',
          3 => 'Menit'
        ];
        return $item->duration.' '.$type[$item->type_satuan];
      })
      ->editColumn('distance', function($item){
        return $item->distance.' Km';
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function detail_cost_datatable($id)
  {
    $item = RouteCostDetail::with('cost_type')->where('header_id', $id)->select('route_cost_details.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.delivery.route.detail.detail_cost.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'  data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('is_internal', function($item){
        $type=[
          1 => 'Internal',
          0 => 'Eksternal',
        ];
        return $type[$item->is_internal];
      })
      ->editColumn('cost', function($item){
        return formatPrice($item->cost);
      })
      ->editColumn('total_liter', function($item){
        if (($item->cost_type->is_bbm ?? null)==1) {
          return formatNumber($item->total_liter);
        } else {
          return "-";
        }
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function vehicle_joint_datatable()
  {
    $item = VehicleJoint::all();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.vehicle.axis.edit')\" ng-click=\"edit($item->id)\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.vehicle.axis.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }

  public function vehicle_variant_datatable()
  {
    $item = VehicleVariant::with('vehicle_joint','vehicle_type','vehicle_manufacturer')->select('vehicle_variants.*');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        //$html="<a ng-show=\"roleList.includes('setting.vehicle.variant.detail')\" ui-sref=\"setting.vehicle_variant.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        $html="<a ng-show=\"roleList.includes('setting.vehicle.variant.edit')\" ui-sref=\"setting.vehicle_variant.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.vehicle.variant.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";

        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function vehicle_type_datatable()
  {
    $item = VehicleType::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.vehicle.vehicle_type.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.vehicle.vehicle_type.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function vehicle_manufacturer_datatable()
  {
    $item = VehicleManufacturer::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.vehicle.manufacturer.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.vehicle.manufacturer.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function vehicle_owner_datatable()
  {
    $item = VehicleOwner::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.vehicle.owner.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.vehicle.owner.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function vendor_type_datatable(Request $request)
  {
    $item = VendorType::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.vendor_type.category.edit')\" ng-click='editVendorType($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.vendor_type.category.delete')\" ng-click=\"deleteVendorType($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function address_type_datatable()
  {
    $item = AddressType::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.general_setting.vendor_type.address_type.edit')\" ng-click='editAddressType($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.general_setting.vendor_type.address_type.delete')\" ng-click=\"deleteAddressType($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->rawColumns(['action'])
      ->make(true);
  }
  public function maintenance_type_datatable()
  {
    $item = VehicleMaintenanceType::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.operational.maintenance_type.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.operational.maintenance_type.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('is_repeat', function($item){
        $type=[
          1 => '<span class="badge badge-success">YA</span>',
          0 => '<span class="badge badge-danger">TIDAK</span>',
        ];
        return $type[$item->is_repeat];
      })
      ->editColumn('type', function($item){
        $type=[
          1 => 'Time Based (Day)',
          2 => 'KM Based (Kilometer)',
        ];
        return $type[$item->type];
      })
      ->editColumn('interval', function($item){
        return formatNumber($item->interval);
      })
      ->editColumn('cost', function($item){
        return formatPrice($item->cost);
      })
      ->rawColumns(['action','is_repeat'])
      ->make(true);
  }
  public function vehicle_checklist_datatable()
  {
    $item = VehicleChecklist::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.vehicle.completeness.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.vehicle.completeness.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('is_active', function($item){
        $type=[
          1 => '<span class="badge badge-success">YA</span>',
          0 => '<span class="badge badge-danger">TIDAK</span>',
        ];
        return $type[$item->is_active];
      })
      ->rawColumns(['action','is_active'])
      ->make(true);
  }
  public function vehicle_body_datatable()
  {
    $item = VehicleBody::query();

    return DataTables::of($item)
      ->addColumn('action', function($item){
        $html="<a ng-show=\"roleList.includes('setting.vehicle.bodies.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.vehicle.bodies.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('is_active', function($item){
        $type=[
          1 => '<span class="badge badge-success">YA</span>',
          0 => '<span class="badge badge-danger">TIDAK</span>',
        ];
        return $type[$item->is_active];
      })
      ->rawColumns(['action','is_active'])
      ->make(true);
  }
  public function route_cost_datatable(Request $request)
  {
    $wr="1=1";
    if (auth()->user()->is_admin==0) {
      $wr.=" AND routes.company_id = ".auth()->user()->company_id;
    }
    if ($request->is_container) {
      $wr.=" AND route_costs.is_container = 1";
    } else {
      $wr.=" AND route_costs.is_container = 0";
    }
    $item = RouteCost::with('vehicle_type','container_type','trayek','commodity')
    ->leftJoin('routes','routes.id','=','route_costs.route_id')
    ->leftJoin('companies','routes.company_id','=','companies.id')
    ->whereRaw($wr)
    ->select('route_costs.*', 'companies.name AS company_name');

    return DataTables::of($item)
      ->addColumn('action', function($item){
        if ($item->is_container==1) {
          $html="<a ng-show=\"roleList.includes('setting.delivery.route_cost.detail')\" ui-sref=\"setting.container_cost.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        } else {
          $html="<a ng-show=\"roleList.includes('setting.delivery.route_cost.detail')\" ui-sref=\"setting.route_cost.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
        }
        $html.="<a ng-show=\"roleList.includes('setting.delivery.route_cost.edit')\" ng-click='edit($item->id)'><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
        $html.="<a ng-show=\"roleList.includes('setting.delivery.route_cost.delete')\" ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o' data-toggle='tooltip' title='Hapus Data'></span></a>";
        return $html;
      })
      ->editColumn('cost', function($item){
        return formatPrice($item->cost);
      })
      ->rawColumns(['action'])
      ->make(true);
  }
}
