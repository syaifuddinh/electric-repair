<?php
namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;
use App\Model\Journal;
use App\Model\Invoice;
use App\Model\JobOrder;
use App\Model\CashTransaction;
use App\Model\CekGiro;
use App\Model\UmSupplier;
use App\Model\UmCustomer;
use App\Model\NotaCredit;
use App\Model\NotaDebet;
use App\Model\SubmissionCost;
use App\Model\CashCount;
use App\Model\CashAdvance;
use App\Model\Bill;
use App\Model\BillDetail;
use App\Model\Debt;
use App\Model\CashMigration;
use App\Model\AssetGroup;
use App\Model\Asset;
use App\Model\AssetSales;
use App\Model\AssetPurchase;
use App\Model\AssetAfkir;
use App\Model\Receivable;
use App\Model\Contact;
use App\Http\Controllers\Finance\KasBonController;
use App\Abstracts\Finance\Receivable AS Rec;
use App\Abstracts\Finance\Payable;
use Carbon\Carbon;
use DataTables;
use DB;
use Response;
use DateTime;

class FinanceApiController extends Controller
{
    /*
      Date : 18-04-2020
      Description : Menampilkan daftar jurnal
      Developer : Didin
      Status : Edit
    */
    public function journal_datatable(Request $request)
    {
        $wr="1=1";

        if (isset($request->filterData['start_date']) && isset($request->filterData['end_date'])) {
            $wr.=" AND date(date_transaction) BETWEEN '".dateDB($request->filterData['start_date'])."' AND '".dateDB($request->filterData['end_date'])."'";
        }

        if (isset($request->filterData['company_id'])) {
            $wr.=" AND company_id = ".$request->filterData['company_id'];
        } else {
            if (auth()->user()->is_admin==0) {
                $wr.=" AND company_id = ".auth()->user()->company_id;
            }
        }
        if (isset($request->filterData['status'])) {
            $wr.=" AND status = ".$request->filterData['status'];
        }
        if (isset($request->filterData['type_transaction_id'])) {
            $wr.=" AND type_transaction_id = ".$request->filterData['type_transaction_id'];
        }

        if (isset($request->filterData['is_audit'])) {
            if($request->filterData['is_audit'] == 1) {

                $wr.=" AND journals.is_audit = 1";
            } else {
                $wr.=" AND journals.is_audit = 0";
            }
        }

        $item = Journal::with('company:id,name','type_transaction')->whereRaw($wr)->select('journals.*');

        if($request->draw == 1) {
            $item = $item->orderBy('journals.id', 'DESC');
        }

        return DataTables::of($item)
        ->addColumn('checkbox', function($item){
            $html="<input type='checkbox' ng-disabled='thisCheck[$item->id]==3' ng-selected='alls' ng-init='thisCheck[$item->id]=$item->status' ng-model='checkData.item[$item->id]' ng-true-value='1' ng-false-value='null'>";
            return $html;
        })
        ->addColumn('action', function($item){
            $html="";
            if ($item->status==2) {
                $html.="<a  ng-show=\"roleList.includes('finance.journal.posting')\" ng-click='postingOne($item->id)' data-toggle='tooltip' title='Posting Jurnal'><span class='fa fa-check'></span>&nbsp;&nbsp;</a>";
            }
            $html.="<a ng-show=\"roleList.includes('finance.journal.delete')\" ui-sref=\"finance.journal.show({id:$item->id})\" data-toggle='tooltip' title='Detail Asset'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            if (in_array($item->source,[1,2]) && $item->status<2) {
                $html.="<a ui-sref=\"finance.journal.edit({id:$item->id})\" data-toggle='tooltip' title='Edit Jurnal'><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
                $html.="<a ng-show=\"roleList.includes('finance.journal.delete')\" ng-click='deletes($item->id)' data-toggle='tooltip' title='Hapus Jurnal'><span class='fa fa-trash-o'></span></a>";
            }
            return $html;
        })
        ->editColumn('status', function($item){
            $status=[
                1 => '<span class="badge badge-warning">Draft</span>',
                2 => '<span class="badge badge-info">Disetujui</span>',
                3 => '<span class="badge badge-success">Posted</span>',
            ];
            return $status[$item->status];
        })
        ->editColumn('debet', function($item){
            return formatNumber($item->debet);
        })
        ->editColumn('credit', function($item){
            return formatNumber($item->credit);
        })
        ->rawColumns(['action','checkbox','status', 'description'])
        ->make(true);
    }

    public function gross_margin_inPercent(Request $request)
    {
        $month = new Carbon(date('Y-m-d', strtotime($request->date)));
        $startPeriode = $month->copy()->startOfMonth()->format('Y-m-d');
        $endPeriode = $month->copy()->endOfMonth()->format('Y-m-d');

// nilai summary job_order dikurangi (-) biaya operasional
        $income = DB::table('job_orders')->selectRaw("sum(total_price) as total")->whereBetween('shipment_date', [$startPeriode, $endPeriode])->first();
        $cost = DB::table('job_order_costs')
        ->leftJoin('job_orders','job_orders.id','job_order_costs.header_id')
        ->whereBetween('job_orders.shipment_date', [$startPeriode,$endPeriode])
        ->whereIn('job_order_costs.status',[3,5])
        ->where('job_order_costs.type', 1)
        ->selectRaw('ifnull(sum(job_order_costs.total_price),0) as total_cost')->first();

        if($income->total == 0) {
            $result = 0;
        }
        else {
            $result = (($income->total - $cost->total_cost)/$income->total)*100;
        }
        $response['data'] = round($result, 2);
        $response['total_income'] = $income->total;
        $response['total_cost'] = $cost->total_cost;
        return Response::json($response,200,[],JSON_NUMERIC_CHECK);
    }

    public function balance_inPercent(Request $request)
    {
        $month = new Carbon(date('Y-m-d', strtotime($request->date)));
        $startPeriode = $month->copy()->startOfMonth()->format('Y-m-d');
        $endPeriode = $month->copy()->endOfMonth()->format('Y-m-d');

// nilai invoice dikurangi (-) biaya operasional
        $invoice = Invoice::selectRaw("sum(grand_total) as total_invoices")->whereBetween('date_invoice', [$startPeriode, $endPeriode])->first();
        $cost = JobOrder::selectRaw("sum(job_order_costs.total_price) as total_cost")->whereBetween('shipment_date', [$startPeriode, $endPeriode])
        ->leftJoin('job_order_costs', 'job_orders.id', 'job_order_costs.header_id')
        ->whereRaw("job_orders.id in (
            select job_order_id from invoice_details
        )")->first();

        $user_id = auth()->id();
        $users = DB::table('users')->join('companies', 'company_id', 'companies.id')->where('users.id', $user_id)->selectRaw('companies.name AS company_name')->first();
        $company_name = $users->company_name;
        $result = (($invoice->total_invoices - $cost->total_cost));
        $response['balance'] = round($result, 2);
        $response['company_name'] = $company_name;
        return Response::json($response,200,[],JSON_NUMERIC_CHECK);
    }

    public function draft_list_piutang_datatable2(Request $request)
    {
        $item = Rec::query($request->all());
        if($request->draw ==1) {
            $item = $item->orderBy('date_transaction', 'desc')->orderBy('receivables.id', 'desc');
        }

        $resp = DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.credit.draft_list_piutang.detail')\" ui-sref='operational.invoice_jual.show({id:$item->invoice_id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

            return $html;
        })
        ->filterColumn('umur', function($query, $keyword) {
            $sql="datediff(date(now()),receivables.date_tempo) like ?";
            $query->whereRaw($sql, ["%{$keyword}%"]);
        })
        ->filterColumn('sisa', function($query, $keyword) {
            $sql="(receivables.debet-receivables.credit) like ?";
            $query->whereRaw($sql, ["%{$keyword}%"]);
        })
        ->editColumn('sisa', function($item){
            if ($item->status_piutang==1) {
                return 0;
            }
            return number_format($item->sisa);
        })
        ->editColumn('code_invoice', function($item){
            if (empty($item->code_invoice) && !empty($item->code))
                return $item->code;

            return ($item->code_invoice);
        })
        ->editColumn('status_piutang', function($item){
            $status=[
                1 => '<span class="badge badge-primary">Lunas</span>',
                2 => '<span class="badge badge-danger">Outstanding</span>',
                3 => '<span class="badge badge-success">Proses</span>',
            ];
            return $status[$item->status_piutang];
        })
        ->rawColumns(['action','status_piutang'])
        ->make(true);

        $raw = $resp->getData();
        $raw->sisa = Rec::getSisa($request->all());
        $resp->setData($raw);

        return $resp;
    }

    public function draft_list_piutang_datatable(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND receivables.company_id = ".auth()->user()->company_id;
        }

        if (auth()->user()->is_customer==1) {
            $wr.=" AND receivables.contact_id = ".auth()->user()->contact_id;
        }


        if ($request->customer_id) {
            $wr.=" and receivables.contact_id = $request->customer_id";
        }


        $item = Receivable::with('type_transaction', 'contact');

        $start_date_invoice = $request->start_date_invoice;
        $start_date_invoice = $start_date_invoice != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date_invoice) : '';

        $end_date_invoice = $request->end_date_invoice;
        $end_date_invoice = $end_date_invoice != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date_invoice) : '';

        $item = $start_date_invoice != '' && $end_date_invoice != '' ? $item->whereBetween('date_transaction', [$start_date_invoice, $end_date_invoice]):$item;

        $start_due_date = $request->start_due_date;
        $start_due_date = $start_due_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_due_date) : '';
        $end_due_date = $request->end_due_date;
        $end_due_date = $end_due_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_due_date) : '';
        $item = $start_due_date != '' && $end_due_date != '' ? $item->whereBetween('date_tempo', [$start_due_date, $end_due_date]):$item;

        $status = $request->status;
        if($status == 1) {
            $item = $item->whereRaw('DATEDIFF(date_tempo, NOW()) > 0');
        }
        else if($status == 2) {
            $item = $item->whereRaw('debet - credit = 0');
        }
        else if($status == 3) {
            $item = $item->whereRaw('DATEDIFF(date_tempo, NOW()) <= 0 AND debet - credit > 0');
        }

        if($request->draw == 1) {
            $item = $item->orderBy('date_transaction', 'desc')->orderBy('receivables.id', 'desc');
        }

        $item = $item->whereRaw($wr)->selectRaw('receivables.*, debet - credit AS sisa_piutang, DATEDIFF(NOW(), date_transaction) AS usia_piutang, DATEDIFF(date_tempo, NOW()) AS usia_jatuh_tempo')->get();


        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ui-sref='operational.invoice_jual.show({id:$item->relation_id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

            return $html;
        })
        ->editColumn('date_transaction', function($item){
            return dateView($item->date_transaction);
        })
        ->editColumn('date_tempo', function($item){
            return dateView($item->date_tempo);
        })
        ->make(true);
    }

    public function cash_transaction_datatable(Request $request)
    {
        $wr="1=1";
        $isMenuKasbon = isset($request->by_user) && $request->by_user == 1;
        if (auth()->user()->is_admin==0) {
            $wr.=" AND cash_transactions.company_id = ".auth()->user()->company_id;
        }

        if ($isMenuKasbon) {
            $wr.=" AND cash_transactions.created_by = " . auth()->id();
        }

        if(isset($request->company_id)) {
            $wr.=" AND cash_transactions.company_id = ".$request->company_id;
        }

        $start_date = $request->start_date;
        $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';

        if ($start_date != '' && $end_date != '') {
            $wr.=" AND cash_transactions.date_transaction BETWEEN '$start_date' AND '$end_date'";
        }
        $item = CashTransaction::with('company','type_transaction')->whereRaw($wr);

        $item = $item->leftJoin('cash_transaction_cost_statuses', 'cash_transaction_cost_statuses.id', 'cash_transactions.status_cost');

        $item = $item->select(
            'cash_transactions.*',
            'cash_transaction_cost_statuses.slug AS status_cost_slug',
            'cash_transaction_cost_statuses.name AS status_cost_name'
        );

        if($request->order[0]['column'] == 8) {
            $item->orderBy('cash_transactions.id', $request->order[0]['dir']);
        }

        return DataTables::of($item)
        ->addColumn('action', function($item) use ($isMenuKasbon){
            $html = "";
            if ($item->status!=3) {
                $html.="<button style='border:none' type='button' class='btn btn-xs btn-default' ng-show=\"roleList.includes('finance.transaction_cash.detail')\" ui-sref=\"finance.cash_transaction.show({id:$item->id})\" data-toggle='tooltip' title='Detail Transaksi Kas'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></button>";
            }

            if (
                $item->status_cost_slug == 'draft' &&
                !in_array($item->status,[2,3]) && 
                $item->couldBeApproved()
            ) {
                $html.="<button style='border:none' type='button' class='btn btn-xs btn-default' ng-show=\"roleList.includes('finance.transaction_cash.submission')\" ng-disabled='disabledApprove[$item->id]' ng-click=\"approve($item->id)\" data-toggle='tooltip' title='Approve Transaksi'><span class='fa fa-check'></span></button>";
            }

            if(!$isMenuKasbon && ($item->type_transaction_id > 4 && $item->type_transaction_id < 9)) {

// if ($item->jenis==1) {
// jika masuk
//     if ($item->is_cut==0 || in_array($item->status,[1])) {
//         $html.="<a  ng-show=\"roleList.includes('finance.transaction_cash.edit')\" ui-sref=\"finance.cash_transaction.edit({id:$item->id})\" data-toggle='tooltip' title='Edit Transaksi Kas'><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
//         $html.="<a ng-show=\"roleList.includes('finance.transaction_cash.delete')\" ng-click=\"deletes($item->id)\" data-toggle='tooltip' title='Hapus Transaksi Kas'><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></a>";
//     }
// } else {
                if (in_array($item->status_cost,[1]) && $item->status==1) {

                    $html.="<button style='border:none' type='button' class='btn btn-xs btn-default' ng-show=\"roleList.includes('finance.transaction_cash.edit')\"  ui-sref=\"finance.cash_transaction.edit({id:$item->id})\" data-toggle='tooltip' title='Edit Transaksi Kas'><span class='fa fa-edit'></span></button>";
                    $html.="<button style='border:none' class='btn btn-xs btn-default' ng-show=\"roleList.includes('finance.transaction_cash.delete')\" ng-click=\"deletes($item->id)\" data-toggle='tooltip' title='Hapus Transaksi Kas'><span class='fa fa-trash'  data-toggle='tooltip' title='Hapus Data'></span></button>";
                }
// }
            }
            return $html;
        })
        ->editColumn('checkbox', function($item){
// $html="<input type='checkbox' ng-click='checking()' ng-init='listData.data[$item->id].value=0' ng-model=\"listData.data[$item->id].value\" ng-true-value=\"1\" ng-false-value=\"0\">";
            $html="";
            return $html;
        })
        ->editColumn('total', function($item){
            return formatNumber($item->total);
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => '<span class="badge badge-primary">Printed</span>',
                2 => '<span class="badge badge-success">Posted</span>',
                3 => '<span class="badge badge-danger">Deleted</span>',
            ];
            return $stt[$item->status] ?? '';
        })
        ->rawColumns(['action','status','checkbox','status_cost'])
        ->make(true);
    }

    public function cek_giro_datatable(Request $request)
    {
        return DataTables::of(self::cek_giro_query($request))
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.giro.detail')\" ui-sref=\"finance.cek_giro.show({id:$item->id})\" data-toggle='tooltip' title='Detail Cek / Giro'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;";
            $html.="<a ng-show=\"roleList.includes('finance.giro.delete')\" ng-click='deletes($item->id)' data-toggle='tooltip' title='Hapus Cek / Giro'><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->addColumn('action_choose', function($item){
            $html="<a ng-click='chooseCekGiro($item->id,\"$item->giro_no\",$item->amount)' class='btn btn-sm btn-primary'>Pilih</a>";
            return $html;
        })
        ->editColumn('amount', function($item){
            return formatNumber($item->amount);
        })
        ->editColumn('type', function($item){
            $stt=[
                1=>'<span class="badge badge-success">Cheque</span>',
                2=>'<span class="badge badge-info">Giro</span>',
            ];
            return $stt[$item->type];
        })
        ->editColumn('is_kliring', function($item){
            $stt=[
                1=>'<span class="fa fa-check-circle fa-2x" style="color:green;"></span>',
                0=>'<span class="fa fa-times-circle fa-2x" style="color:red;"></span>',
            ];
            return $stt[$item->is_kliring];
        })
        ->editColumn('is_empty', function($item){
            $stt=[
                1=>'<span class="fa fa-check-circle fa-2x" style="color:green;"></span>',
                0=>'<span class="fa fa-times-circle fa-2x" style="color:red;"></span>',
            ];
            return $stt[$item->is_empty];
        })
        ->rawColumns(['action','type','is_kliring','is_empty','action_choose'])
        ->make(true);
    }

    public function um_supplier_datatable(Request $request)
    {
        $wr="1=1";

        $item = UmSupplier::leftJoin('type_transactions','type_transactions.id','=','um_suppliers.type_transaction_id')
        ->leftJoin('contacts','contacts.id','=','um_suppliers.contact_id')
        ->leftJoin('journals','journals.code','=','um_suppliers.code')
        ->leftJoin('companies','companies.id','=','um_suppliers.company_id')
        ->when(!auth()->user()->is_admin, function($query) {
            $query->where('um_suppliers.company_id', auth()->user()->company_id);
        })
        ->where('type_transactions.slug','!=','saldoAwal')
        ->whereRaw($wr);

        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_transaction', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

        $start_debet = $request->start_debet;
        $start_debet = $start_debet != null ? str_replace(',', '', $start_debet) : 0;
        $end_debet = $request->end_debet;
        $end_debet = $end_debet != null ? str_replace(',', '', $end_debet) : 0;
        $item = $end_debet != 0 ? $item->whereBetween('debet', [$start_debet, $end_debet]) : $item;


        $start_credit = $request->start_credit;
        $start_credit = $start_credit != null ? str_replace(',', '', $start_credit) : 0;
        $end_credit = $request->end_credit;
        $end_credit = $end_credit != null ? str_replace(',', '', $end_credit) : 0;
        $item = $end_credit != 0 ? $item->whereBetween('credit', [$start_credit, $end_credit]) : $item;


        $start_sisa = $request->start_sisa;
        $start_sisa = $start_sisa != null ? str_replace(',', '', $start_sisa) : 0;
        $end_sisa = $request->end_sisa;
        $end_sisa = $end_sisa != null ? str_replace(',', '', $end_sisa) : 0;
        $item = $end_sisa != 0 ? $item->whereBetween( DB::raw("um_suppliers.debet-um_suppliers.credit as sisa"), [$start_sisa, $end_sisa]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;


        $supplier_id = $request->supplier_id;
        $supplier_id = $supplier_id != null ? $supplier_id : '';
        $item = $supplier_id != '' ? $item->where('supplier_id', $supplier_id) : $item;

        $item->select('um_suppliers.*','contacts.name as cname','companies.name as coname',DB::raw("um_suppliers.debet-um_suppliers.credit as sisa"), 'journals.status AS journal_status')
        ->orderByRaw('um_suppliers.id DESC');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.deposite.supplier.detail')\"  ui-sref=\"finance.um_supplier.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            if($item->journal_status==1 || $item->journal_status==2) {
                $html.="<a ng-show=\"roleList.includes('finance.deposite.supplier.delete')\" ng-click=\"deletes($item->id)\" data-toggle='tooltip' title='Hapus'><span class='fa fa-trash-o'></span></a>";
            }
            return $html;
        })
        ->editColumn('date_transaction', function($item){
            return dateView($item->date_transaction);
        })
        ->editColumn('debet', function($item){
            return formatPrice($item->debet);
        })
        ->editColumn('credit', function($item){
            return formatPrice($item->credit);
        })
        ->editColumn('sisa', function($item){
            return formatPrice($item->sisa);
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function um_customer_datatable(Request $request)
    {

        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND um_customers.company_id = ".auth()->user()->company_id;
        }
        $item = UmCustomer::leftJoin('type_transactions','type_transactions.id','=','um_customers.type_transaction_id')
        ->join('um_customer_details', 'header_id', 'um_customers.id')
        ->leftJoin('contacts','contacts.id','=','um_customers.contact_id')
        ->leftJoin('companies','companies.id','=','um_customers.company_id')
        ->leftJoin('journals','journals.code','=','um_customers.code')
        ->where('type_transactions.slug','!=','saldoAwal')
        ->whereRaw($wr);


        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_transaction', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

        $start_debet = $request->start_debet;
        $start_debet = $start_debet != null ? str_replace(',', '', $start_debet) : 0;
        $end_debet = $request->end_debet;
        $end_debet = $end_debet != null ? str_replace(',', '', $end_debet) : 0;
        $item = $end_debet != 0 ? $item->whereBetween('debet', [$start_debet, $end_debet]) : $item;


        $start_credit = $request->start_credit;
        $start_credit = $start_credit != null ? str_replace(',', '', $start_credit) : 0;
        $end_credit = $request->end_credit;
        $end_credit = $end_credit != null ? str_replace(',', '', $end_credit) : 0;
        $item = $end_credit != 0 ? $item->whereBetween('credit', [$start_credit, $end_credit]) : $item;


        $start_sisa = $request->start_sisa;
        $start_sisa = $start_sisa != null ? str_replace(',', '', $start_sisa) : 0;
        $end_sisa = $request->end_sisa;
        $end_sisa = $end_sisa != null ? str_replace(',', '', $end_sisa) : 0;
        $item = $end_sisa != 0 ? $item->whereBetween( DB::raw("um_customers.credit-um_customers.debet as sisa"), [$start_sisa, $end_sisa]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where(DB::raw('um_customers.company_id'), $company_id) : $item;


        $customer_id = $request->customer_id;
        $customer_id = $customer_id != null ? $customer_id : '';
        $item = $customer_id != '' ? $item->where(DB::raw('contacts.id'), $customer_id) : $item;

        $item = $item->selectRaw('um_customers.*, contacts.name as cname, companies.name as coname, um_customers.credit-um_customers.debet as sisa, journals.status AS journal_status')
        ->groupBy('um_customers.id')
        ->orderByRaw('um_customers.id DESC');;


        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.deposite.customer.detail')\"  ui-sref=\"finance.um_customer.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data' data-toggle='tooltip' title='Show Detail'></span></a>&nbsp;&nbsp;";
            if($item->journal_status==1 || $item->journal_status==2) {
                $html.="<a ng-show=\"roleList.includes('finance.deposite.customer.delete')\"  ng-click=\"deletes($item->id)\" data-toggle='tooltip' title='Hapus'><span class='fa fa-trash-o'></span></a>";
            }
            return $html;
        })
        ->editColumn('date_transaction', function($item){
            return dateView($item->date_transaction);
        })
        ->editColumn('debet', function($item){
            return formatPrice($item->debet);
        })
        ->editColumn('credit', function($item){
            return formatPrice($item->credit);
        })
        ->editColumn('lebih_bayar', function($item){
            return formatPrice($item->lebih_bayar);
        })
        ->editColumn('sisa', function($item){
            return formatPrice($item->sisa);
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function nota_credit_datatable(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND nota_credits.company_id = ".auth()->user()->company_id;
        }
        $item=NotaCredit::with('company','contact')->whereRaw($wr);

        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_transaction', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where(DB::raw('company_id'), $company_id) : $item;


        $item = $item->select('nota_credits.*');

        if($request->draw == 1) {
            $item = $item->orderBy('id', 'DESC');
        }


        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.noted.sell.detail')\" ui-sref=\"finance.nota_credit.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            $html.="<a ng-show=\"roleList.includes('finance.noted.sell.delete')\" ng-click=\"deletes($item->id)\" data-toggle='tooltip' title='Hapus'><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->editColumn('amount', function($item){
            return formatPrice($item->amount);
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function nota_debet_datatable(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND nota_debets.company_id = ".auth()->user()->company_id;
        }
        $item=NotaDebet::with('company','contact')->whereRaw($wr);

        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_transaction', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where(DB::raw('company_id'), $company_id) : $item;

        $item = $item->select('nota_debets.*');

        if($request->draw == 1) {
            $item = $item->orderBy('id', 'DESC');
        }

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.noted.purchase.detail')\" ui-sref=\"finance.nota_debet.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            $html.="<a ng-show=\"roleList.includes('finance.noted.purchase.delete')\" ng-click=\"deletes($item->id)\" data-toggle='tooltip' title='Hapus'><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->editColumn('date_transaction', function($item){
            return dateView($item->date_transaction);
        })
        ->editColumn('amount', function($item){
            return formatPrice($item->amount);
        })
        ->rawColumns(['action'])
        ->make(true);
    }
    public function cash_count_datatable(Request $request)
    {
        $item = self::cash_count_query($request);

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.cash_count.detail')\" ui-sref=\"finance.cash_count.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

            return $html;
        })
        ->editColumn('date_transaction', function($item){
            return dateView($item->date_transaction);
        })
        ->editColumn('saldo_awal', function($item){
            return formatPrice($item->saldo_awal);
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function pajak_datatable(Request $request)
    {
        DB::table('tax_invoices')
        ->whereRaw('DATEDIFF(expiry_date, NOW()) < 0')
        ->update([
            'is_active' => 0
        ]);

        $item = DB::table('tax_invoices')
        ->leftJoin('invoices', 'invoices.id', 'tax_invoices.invoice_id')
        ->select('tax_invoices.id', 'tax_invoices.code', 'tax_invoices.expiry_date', 'tax_invoices.start_date', 'tax_invoices.is_active', 'invoices.code AS invoice_code');

        return DataTables::of($item)
        ->make(true);
    }
    public function kas_bon_datatable(Request $request)
    {
        $conditions = "1=1";
        $user = auth()->user();

        KasBonController::postingKedaluwarsa();

        $items = CashAdvance::with(['company', 'employee', 'statusAkhir', 'statuses']);

        if($user->is_admin == 0 || (isset($request->isManager) && $request->isManager == 1))
            $items->whereRaw("cash_advances.company_id = {$user->company_id}");
        else if($user->is_pegawai == 1)
            $items->whereRaw("cash_advances.employee_id = {$user->contact_id}");

        if( isset($request->is_today) ) {
            $items->whereRaw("status >= 3 AND status <= 5");
        } else if(isset($request->isManager) && $request->isManager == 0){
            $items->whereDoesntHave('statusAkhir', function ($query) {
                $query->whereRaw('status > 5');
            });
        }

        if( isset($request->cash_count_id) ) {
            $items = $items->whereRaw("cash_advances.id IN (SELECT cash_advance_id FROM cash_advance_reports WHERE cash_count_id = $request->cash_count_id)");
        }

        if($request->order[0]['column'] == 0) {
            $items->orderBy('cash_advances.date_transaction', $request->order[0]['dir'])->orderBy('cash_advances.id', $request->order[0]['dir']);
        }
        $items->select('cash_advances.*');

        return DataTables::of($items)
        ->addColumn('reapprovals', function($item){
            return $item->reapprovalsCount();
        })
        ->addColumn('action', function($item){
            $html = "<a ng-show=\"roleList.includes('finance.cash_bon.detail')\" "
            . "ui-sref=\"finance.kas_bon.show({id:$item->id})\" "
            . "data-toggle='tooltip' title='Show Detail'>"
            . "<span class='fa fa-folder-o'></span></a>&nbsp;&nbsp;";

            if ($item->statusAkhir->status == 1) {
                $html .= ("<a data-toggle='tooltip' title='Edit Data' "
                    . "ng-show=\"roleList.includes('finance.cash_bon.edit')\" "
                    . "ui-sref=\"finance.kas_bon.edit({id:$item->id})\">"
                    . "<span class='fa fa-edit'></span></a>&nbsp;&nbsp;");
            }
            return $html;
        })
        ->editColumn('date_transaction', function($item){
            return dateView($item->date_transaction);
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => '<span class="badge badge-warning">BELUM DISETUJUI</span>',
                2 => '<span class="badge badge-warning">SUDAH DISETUJUI</span>',
                3 => '<span class="badge badge-primary">AKTIF</span>',
                4 => '<span class="badge badge-primary">REAPPROVAL</span>',
                5 => '<span class="badge badge-warning">KEDALUWARSA</span>',
                6 => '<span class="badge badge-success">SELESAI</span>',
                7 => '<span class="badge badge-danger">DITOLAK</span>'
            ];
            return $stt[$item->statusAkhir->status];
        })
        ->editColumn('total_cash_advance', function($item){
            return formatPrice($item->total_cash_advance);
        })
        ->rawColumns(['action','status'])
        ->toJson();
    }

    public function bill_datatable(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND bills.company_id = ".auth()->user()->company_id;
        }
        $item=Bill::with('company','customer')->whereRaw($wr);

        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_request', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;

        $customer_id = $request->customer_id;
        $customer_id = $customer_id != null ? $customer_id : '';
        $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;

        $status = $request->status;
        $status = $status != null ? $status : '';
        $item = $status != '' ? $item->where(DB::raw('bills.status'), $status) : $item;

        $item->select('bills.*')
        ->orderBy('bills.id', 'desc');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.debt.draft.detail')\" ui-sref=\"finance.bill_receivable.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
// if ($item->status==1) {
//   $html.="<a ui-sref=\"finance.bill_receivable.edit({id:$item->id})\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
//   $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
// }
            return $html;
        })
        ->addColumn('invoice', function($item){
            $details = BillDetail::where('header_id', $item->id)->get();
            $invoices = [];
            $html = '';

            foreach($details as $billDetail) {
                $invoices []= $billDetail->code;
            }

            if(!empty($invoices)) {
                foreach($invoices as $invoice) {
                    $html .= "- {$invoice}<br/>";
                }
            }

            return $html;
        })
        ->editColumn('date_request', function($item){
            return dateView($item->date_request);
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => '<span class="badge badge-danger">BELUM TERBAYAR</span>',
                2 => '<span class="badge badge-success">TERBAYAR TANPA BP</span>',
                3 => '<span class="badge badge-warning">TERBAYAR DENGAN BP</span>',
            ];
            return $stt[$item->status];
        })
        ->editColumn('total', function($item){
            return formatPrice($item->total);
        })
        ->rawColumns(['action','status', 'invoice'])
        ->make(true);
    }
    public function debt_datatable(Request $request)
    {
        return DataTables::of(self::debt_query($request))
        ->addColumn('action', function($item){
            $html="<a  ng-show=\"roleList.includes('finance.credit.draft.detail')\" ui-sref=\"finance.debt_payable.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            if ($item->status==1) {
                $html.="<a ui-sref=\"finance.debt_payable.edit({id:$item->id})\" data-toggle='tooltip' title='Edit Data'><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
                $html.="<a ng-click=\"deletes($item->id)\" data-toggle='tooltip' title='Hapus Data'><span class='fa fa-trash-o'></span></a>";
            }
            return $html;
        })
        ->editColumn('date_request', function($item){
            return dateView($item->date_request);
        })
        ->addColumn('kode_invoice', function($item) {
            return $item->kode_invoice;
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => '<span class="badge badge-danger">BELUM TERBAYAR</span>',
                2 => '<span class="badge badge-success">TERBAYAR TANPA BP</span>',
                3 => '<span class="badge badge-warning">TERBAYAR DENGAN BP</span>',
            ];
            return $stt[$item->status];
        })
        ->editColumn('total', function($item){
            return formatPrice($item->total);
        })
        ->rawColumns(['action','status','description'])
        ->make(true);
    }
    public function bill_payment_datatable(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND bills.company_id = ".auth()->user()->company_id;
        }
        $item=Bill::with('company','customer')->where('status', 2)->orWhere('status',3)->whereRaw($wr);


        $start_date_request = $request->start_date_request;
        $start_date_request = $start_date_request != null ? new DateTime($start_date_request) : '';
        $end_date_request = $request->end_date_request;
        $end_date_request = $end_date_request != null ? new DateTime($end_date_request) : '';
        $item = $start_date_request != '' && $end_date_request != '' ? $item->whereBetween('date_request', [$start_date_request->format('Y-m-d'), $end_date_request->format('Y-m-d')]) : $item;


        $start_date_receive = $request->start_date_receive;
        $start_date_receive = $start_date_receive != null ? new DateTime($start_date_receive) : '';
        $end_date_receive = $request->end_date_receive;
        $end_date_receive = $end_date_receive != null ? new DateTime($end_date_receive) : '';
        $item = $start_date_receive != '' && $end_date_receive != '' ? $item->whereBetween('date_receive', [$start_date_receive->format('Y-m-d'), $end_date_receive->format('Y-m-d')]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;

        $customer_id = $request->customer_id;
        $customer_id = $customer_id != null ? $customer_id : '';
        $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;

        $status = $request->status;
        $status = $status != null ? $status : '';
        $item = $status != '' ? $item->where(DB::raw('bills.status'), $status) : $item;

        $item = $item->select('bills.*')->orderByRaw('date_request desc, id desc');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.debt.payment.detail')\"  ui-sref=\"finance.bill_payment.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
// if ($item->status==1) {
//   $html.="<a ui-sref=\"finance.bill_receivable.edit({id:$item->id})\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
//   $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
// }
            return $html;
        })
        ->editColumn('date_request', function($item){
            return dateView($item->date_request);
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => '<span class="badge badge-danger">BELUM TERBAYAR</span>',
                2 => '<span class="badge badge-success">TERBAYAR TANPA BP</span>',
                3 => '<span class="badge badge-warning">TERBAYAR DENGAN BP</span>',
            ];
            return $stt[$item->status];
        })
        ->editColumn('paid', function($item){
            return formatPrice($item->paid);
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }
    public function debt_payment_datatable(Request $request)
    {
        return DataTables::of(self::debt_payment_query($request))
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.credit.payment.detail')\"  ui-sref=\"finance.debt_payment.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
// if ($item->status==1) {
//   $html.="<a ui-sref=\"finance.bill_receivable.edit({id:$item->id})\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
//   $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
// }
            return $html;
        })
        ->editColumn('date_request', function($item){
            return dateView($item->date_request);
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => '<span class="badge badge-danger">BELUM TERBAYAR</span>',
                2 => '<span class="badge badge-success">TERBAYAR TANPA BP</span>',
                3 => '<span class="badge badge-warning">TERBAYAR DENGAN BP</span>',
            ];
            return $stt[$item->status];
        })
        ->editColumn('paid', function($item){
            return formatPrice($item->paid);
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }
    public function submission_cost_datatable(Request $request)
    {

        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" and IF(submission_costs.type_submission = 1,job_orders.company_id=".auth()->user()->company_id.",IF(submission_costs.type_submission=2,manifests.company_id=".auth()->user()->company_id.",cash_transactions.company_id=".auth()->user()->company_id."))";
        }

        $item=DB::table('submission_costs')
        ->leftJoin('companies','companies.id','submission_costs.company_id')
        ->leftJoin('manifest_costs','manifest_costs.id','submission_costs.relation_cost_id')
        ->leftJoin('manifests','manifests.id','manifest_costs.header_id')
        ->leftJoin('job_order_costs','job_order_costs.id','submission_costs.relation_cost_id')
        ->leftJoin('job_orders','job_orders.id','job_order_costs.header_id')
        ->leftJoin('cash_transactions','cash_transactions.id','submission_costs.relation_cost_id')
        ->whereRaw($wr)
        ->selectRaw('
            submission_costs.id,
            submission_costs.type_submission,
            (CASE submission_costs.type_submission
            WHEN 2 THEN
            manifests.code
            WHEN 1 THEN
            job_orders.code
            WHEN 4 THEN
            cash_transactions.code
            ELSE
            \'-\'
            END) as codes,
            (CASE submission_costs.type_submission
            WHEN 2 THEN
            manifest_costs.total_price
            WHEN 1 THEN
            job_order_costs.total_price
            WHEN 4 THEN
            cash_transactions.total
            ELSE
            \'-\'
            END) as amount,
            submission_costs.date_submission,
            submission_costs.description,
            submission_costs.status,
            companies.name as cname,
            submission_costs.created_at
            ')->orderBy('submission_costs.created_at', 'DESC');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.submission_cost.detail')\" ui-sref=\"finance.submission_cost.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            return $html;
        })
        ->filterColumn('codes', function($query, $keyword) {
            $sql="(CASE submission_costs.type_submission
            WHEN 2 THEN
            manifests.code
            WHEN 1 THEN
            job_orders.code
            WHEN 4 THEN
            cash_transactions.code
            ELSE
            '-'
            END) like ?";
            $query->whereRaw($sql, ["%{$keyword}%"]);
        })
        ->filterColumn('amount', function($query, $keyword) {
            $sql="(CASE submission_costs.type_submission
            WHEN 2 THEN
            manifest_costs.total_price
            WHEN 1 THEN
            job_order_costs.total_price
            WHEN 4 THEN
            cash_transactions.total
            ELSE
            '-'
            END) like ?";
            $query->whereRaw($sql, ["%{$keyword}%"]);
        })
        ->editColumn('date_submission', function($item){
            return dateView($item->date_submission);
        })
        ->editColumn('amount', function($item){
            return formatPrice($item->amount);
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => '<span class="badge badge-info">Diajukan</span>',
                2 => '<span class="badge badge-success">Disetujui</span>',
                3 => '<span class="badge badge-danger">Ditolak</span>',
                4 => '<span class="badge badge-primary">Diposting</span>',
                5 => '<span class="badge badge-danger">Revisi</span>',
            ];
            return $stt[$item->status];
        })
        ->editColumn('type_submission', function($item){
            $stt=[
                1 => '<span class="badge badge-info">JOB ORDER</span>',
                2 => '<span class="badge badge-success">PACKING LIST</span>',
                3 => '<span class="badge badge-danger">PICKUP ORDER</span>',
                4 => '<span class="badge badge-primary">TRANSAKSI KAS</span>',
                5 => '<span class="badge badge-warning">KAS BON</span>',
            ];
            return $stt[$item->type_submission];
        })
        ->rawColumns(['action','status','type_submission'])
        ->toJson();
    }
    public function cash_migration_datatable(Request $request)
    {
        $item = self::cash_migration_query($request);

        return DataTables::of($item)
        ->addColumn('action_realisation', function($item){
            $html="<a ui-sref=\"finance.realisasi_mutasi.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            return $html;
        })
        ->editColumn('total', function($item){
            return formatNumber($item->total);
        })
        ->addColumn('status_label', function($item){
            $stt=[
                1 => '<span class="badge badge-warning">Pengajuan</span>',
                2 => '<span class="badge badge-success">Disetujui Keuangan</span>',
                3 => '<span class="badge badge-primary">Disetujui Direksi</span>',
                4 => '<span class="badge badge-info">Realisasi</span>',
                5 => '<span class="badge badge-danger">Tolak</span>'
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['status_label','action_realisation'])
        ->toJson();
    }
    public function asset_group_datatable()
    {
        $item=AssetGroup::query();

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="";
// $html="<a ui-sref=\"finance.depresiasi_asset.show({id:$item->id})\"><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            $html.="<a ng-show=\"roleList.includes('finance.asset.group.edit')\" ui-sref=\"finance.kelompok_asset.edit({id:$item->id})\" data-toggle='tooltip' title='Edit Kelompok Asset'><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
            $html.="<a ng-show=\"roleList.includes('finance.asset.group.delete')\" ng-click=\"deletes($item->id)\" data-toggle='tooltip' title='Hapus Kelompok Asset'><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->editColumn('method', function($item){
            $stt=[
                1 => '<span class="badge badge-success">Garis Lurus</span>',
            ];
            return $stt[$item->method];
        })
        ->rawColumns(['action','method'])
        ->make(true);
    }

    public function saldo_asset_datatable()
    {
//tampilkan yang status sudah disetujui
        $item=Asset::with('company','asset_group')->whereRaw("status = 2 and is_saldo = 1")->select('assets.*');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="";
            $html="<a ng-show=\"roleList.includes('finance.asset.first_saldo_asset.detail')\" ui-sref=\"finance.saldoawal_asset.show({id:$item->id})\" data-toggle='tooltip' title='Detail Asset'><span class='fa fa-folder-o' data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
// $html.="<a ui-sref=\"finance.saldoassset.edit({id:$item->id})\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
// $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->editColumn('method', function($item){
            $stt=[
                1 => '<span class="badge badge-success">Garis Lurus</span>',
            ];
            return $stt[$item->method];
        })
        ->editColumn('asset_type', function($item){
            $stt=[
                1 => 'Asset Berwujud',
                2 => 'Asset Tak Berwujud',
            ];
            return $stt[$item->asset_type];
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => 'Draft',
                2 => 'Available',
                3 => 'Afkir (Tidak Aktif)'
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['action','method','asset_type'])
        ->make(true);
    }
    public function asset_datatable(Request $request)
    {
        //tampilkan yang status sudah disetujui
        $item = DB::table('assets');
        $item = $item->leftJoin('companies as c','c.id','assets.company_id');
        $item = $item->leftJoin('journals as j','j.id','assets.journal_id');
        $item = $item->leftJoin('asset_groups as ag','ag.id','assets.asset_group_id');
        $item = $item->whereRaw("assets.status = 2");

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';

        $asset_type = $request->asset_type;
        $asset_type = $asset_type != null ? $asset_type : '';
        $item = $asset_type != '' ? $item->where('asset_type', $asset_type) : $item;

        $item = $item->selectRaw('assets.*,c.name as comp, ag.name as ag_name, j.status as j_status');
        if($request->draw == 1) {
            $item->orderBy('assets.id', 'DESC');
        }

        return DataTables::of($item)
        ->editColumn('method', function($item){
            $stt=[
                1 => '<span class="badge badge-success">Garis Lurus</span>',
            ];
            return $stt[$item->method];
        })
        ->editColumn('asset_type', function($item){
            $stt=[
                1 => 'Asset Berwujud',
                2 => 'Asset Tak Berwujud',
            ];
            return $stt[$item->asset_type];
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => 'Draft',
                2 => 'Available',
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['method','asset_type'])
        ->make(true);
    }

    public function asset_depreciation_datatable(Request $request)
    {
//tampilkan yang status sudah disetujui
        $wr="1=1";
        if ($request->asset_id) {
            $wr.=" and asset_depreciations.header_id = $request->asset_id";
        }
        if ($request->start_date && $request->end_date) {
            $start=Carbon::parse($request->start_date)->format('Y-m-d');
            $end=Carbon::parse($request->end_date)->format('Y-m-d');
            $wr.=" and asset_depreciations.date_utility between '$start' and '$end'";
        }

        $item=DB::table('asset_depreciations')
        ->leftJoin('assets','assets.id','=','asset_depreciations.header_id')
        ->select([
            'asset_depreciations.*',
            'assets.name as asset_name',
            'assets.code as asset_code'
        ])
        ->orderBy('assets.id', 'DESC');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="";
            $html="<a ng-show=\"roleList.includes('finance.asset.depreciation.detail')\" ui-sref=\"finance.depresiasi_asset.show({id:$item->id})\" data-toggle='tooltip' title='Detail Asset'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
// $html.="<a ui-sref=\"finance.saldoassset.edit({id:$item->id})\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
// $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => 'Draft',
                2 => 'Available',
            ];
            return $stt[$item->status];
        })
        ->editColumn('depreciation_cost', function($item){
            return formatNumber($item->depreciation_cost);
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function asset_purchase_datatable(Request $request)
    {
        $item=AssetPurchase::with('company','supplier')->select('asset_purchases.*');

        return DataTables::of($item)
        ->addColumn('action', function($item) {
            $html = "<a ui-sref=\"finance.pembelian_asset.show({id:$item->id})\" data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            $html .= "<a ui-sref=\"finance.pembelian_asset.edit({id:$item->id})\" data-toggle='tooltip' title='Edit'><span class='fa fa-edit'  ></span></a>&nbsp;&nbsp;";
// $html.="<a ui-sref=\"finance.saldoassset.edit({id:$item->id})\"><span class='fa fa-edit'></span></a>&nbsp;&nbsp;";
// $html.="<a ng-click=\"deletes($item->id)\"><span class='fa fa-trash-o'></span></a>";
            return $html;
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => 'Permintaan Belum Disetujui',
                2 => 'Disetujui',
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['action','status'])
        ->make(true);
    }

    public function daftarasset_datatable(Request $request)
    {
//tampilkan yang status sudah disetujui
        $item=Asset::with('company','asset_group')->whereRaw("status = 2");

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';

        $asset_type = $request->asset_type;
        $asset_type = $asset_type != null ? $asset_type : '';
        $item = $asset_type != '' ? $item->where('asset_type', $asset_type) : $item;

        $item = $item->select('assets.*');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="";
            $html="<a ng-show=\"roleList.includes('finance.asset.list_asset')\" ui-sref=\"finance.daftar_asset.show({id:$item->id})\" data-toggle='tooltip' title='Detail Asset'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            return $html;
        })
        ->editColumn('method', function($item){
            $stt=[
                1 => '<span class="badge badge-success">Garis Lurus</span>',
            ];
            return $stt[$item->method];
        })
        ->editColumn('asset_type', function($item){
            $stt=[
                1 => 'Asset Berwujud',
                2 => 'Asset Tak Berwujud',
            ];
            return $stt[$item->asset_type];
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => 'Draft',
                2 => 'Available',
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['action','method','asset_type'])
        ->make(true);
    }

    public function asset_afkir_datatable(Request $request)
    {

        $item = DB::table('asset_afkirs')
        ->leftJoin('companies','companies.id','asset_afkirs.company_id')
        ->leftJoin('assets','assets.id','asset_afkirs.asset_id')
        ->selectRaw('
            asset_afkirs.*,
            companies.name as company,
            assets.name as asset
            ');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.asset.rejected.detail')\" ui-sref='finance.pengafkiran_asset.show({id:$item->id})' data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";
            if($item->status < 2)
                $html.="<a ng-show=\"roleList.includes('finance.asset.rejected.edit')\" ui-sref=\"finance.pengafkiran_asset.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";
            return $html;
        })
        ->editColumn('date_transaction', function($item){
            return dateView($item->date_transaction);
        })
        ->editColumn('loss_amount', function($item){
            return number_format($item->loss_amount);
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public function asset_sales_datatable(Request $request)
    {
        $item = AssetSales::with('company','costumer')->select('asset_sales.*')->orderBy('date_transaction', 'desc');

        return DataTables::of($item)
        ->addColumn('action', function($item){
            $html="<a ng-show=\"roleList.includes('finance.asset.sell.detail')\" ui-sref='finance.penjualan_asset.show({id:$item->id})' data-toggle='tooltip' title='Show Detail'><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

            if($item->status < 2)
                $html.="<a ng-show=\"roleList.includes('finance.asset.sell.edit')\" ui-sref=\"finance.penjualan_asset.edit({id:$item->id})\"><span class='fa fa-edit' data-toggle='tooltip' title='Edit Data'></span></a>&nbsp;&nbsp;";

            return $html;
        })
        ->editColumn('total_price', function($item){
            return number_format($item->total_price);
        })
        ->editColumn('status', function($item){
            $stt=[
                1 => 'Belum Disetujui',
                2 => 'Disetujui',
            ];
            return $stt[$item->status];
        })
        ->rawColumns(['action'])
        ->make(true);
    }

    public static function kas_bon_query(Request $request)
    {
        $wr="1=1";
        if ($request->company_id)
            $wr.=" AND cash_advances.company_id = $request->company_id";
        else if (auth()->user()->is_admin==0)
            $wr.=" AND cash_advances.company_id = ".auth()->user()->company_id;

        if($request->start_date)
            $wr.= ' AND cash_advances.date_transaction >= "' .dateDB($request->start_date). '"';

        if($request->end_date)
            $wr.= ' AND cash_advances.date_transaction <= "' .dateDB($request->end_date). '"';

        if($request->status)
            $wr.= " AND cash_advances.status = {$request->status}";

        return CashAdvance::with('company','employee')->whereRaw($wr)->select('cash_advances.*');
    }

    public static function cash_count_query(Request $request)
    {
        $wr="1=1";
        if ($request->company_id)
            $wr.=" AND cash_counts.company_id = $request->company_id";
        else if (auth()->user()->is_admin==0)
            $wr.=" AND cash_counts.company_id = ".auth()->user()->company_id;

        if($request->start_date)
            $wr.= ' AND cash_counts.date_transaction >= "' .dateDB($request->start_date). '"';

        if($request->end_date)
            $wr.= ' AND cash_counts.date_transaction <= "' .dateDB($request->end_date). '"';

        $item = CashCount::with('company:id,name', 'approved_by:id,name')->whereRaw($wr)->select('cash_counts.*');
        if($request->draw == 1) {
            $item = $item->orderBy('cash_counts.id', 'DESC');
        }
        return $item;
    }

    public static function submission_cost_query(Request $request)
    {
        $wr="1=1";
        if ($request->company_id)
            $wr.=" AND submission_costs.company_id = $request->company_id";
        else if (auth()->user()->is_admin==0)
            $wr.=" AND submission_costs.company_id = ".auth()->user()->company_id;

        if($request->start_submit_date)
            $wr.= ' AND submission_costs.created_at >= "' .dateDB($request->start_submit_date) . ' 00:00:00'. '"';

        if($request->end_submit_date)
            $wr.= ' AND submission_costs.created_at <= "' .dateDB($request->end_submit_date) . ' 23:59:59'. '"';

        if($request->start_cost_date)
            $wr.= ' AND submission_costs.date_submission >= "' .dateDB($request->start_cost_date). '"';

        if($request->end_cost_date)
            $wr.= ' AND submission_costs.date_submission <= "' .dateDB($request->end_cost_date). '"';

        if($request->jenis)
            $wr.= " AND submission_costs.type_submission = {$request->jenis} ";

        if($request->status)
            $wr.= " AND submission_costs.status = {$request->status} ";

        return DB::table('submission_costs')
        ->leftJoin('companies','companies.id','submission_costs.company_id')
        ->leftJoin('manifest_costs','manifest_costs.id','submission_costs.relation_cost_id')
        ->leftJoin('manifests','manifests.id','manifest_costs.header_id')
        ->leftJoin('job_order_costs','job_order_costs.id','submission_costs.relation_cost_id')
        ->leftJoin('job_orders','job_orders.id','job_order_costs.header_id')
        ->leftJoin('cash_transactions','cash_transactions.id','submission_costs.relation_cost_id')
        ->whereRaw($wr)
        ->selectRaw('
            submission_costs.id,
            submission_costs.type_submission,
            (CASE submission_costs.type_submission
            WHEN 2 THEN
            manifests.code
            WHEN 1 THEN
            job_orders.code
            WHEN 4 THEN
            cash_transactions.code
            ELSE
            \'-\'
            END) as codes,
            (CASE submission_costs.type_submission
            WHEN 2 THEN
            manifest_costs.total_price
            WHEN 1 THEN
            job_order_costs.total_price
            WHEN 4 THEN
            cash_transactions.total
            ELSE
            \'-\'
            END) as amount,
            submission_costs.date_submission,
            submission_costs.description,
            submission_costs.status,
            companies.name as cname,
            submission_costs.created_at
            ');
    }

    public static function cash_transaction_query(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND cash_transactions.company_id = ".auth()->user()->company_id;
        }

        $item = CashTransaction::with('company','type_transaction')->whereRaw($wr);

        if(isset($request->company_id)) {
            $item = $item->where('company_id', $request->company_id);
        }
        if(isset($request->status)) {
            $item = $item->where('status', $request->status);
        }
        if(isset($request->status_cost)) {
            $item = $item->where('status_cost', $request->status_cost);
        }

        $start_date = $request->start_date;
        $start_date = $start_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? preg_replace('/(\d+)-(\d+)-(\d+)/', '$3-$2-$1', $end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_transaction', [$start_date, $end_date]) : $item;

        return $item->select('cash_transactions.*');
    }

    public static function cash_migration_query(Request $request)
    {
        $wr="1=1";

        if ($request->source_company_id)
            $wr.=" AND cash_migrations.company_from = $request->source_company_id";

        if ($request->dest_company_id)
            $wr.=" AND cash_migrations.company_to = $request->dest_company_id";

        if (auth()->user()->is_admin==0)
            $wr.=" and (cash_migrations.company_from = ".auth()->user()->company_id." or cash_migrations.company_to = ".auth()->user()->company_id.")";

        if ($request->status)
            $wr.=" AND cash_migrations.status = $request->status";

        if ($request->statusIn)
            $wr.=" AND cash_migrations.status IN ($request->statusIn)";

        $item=DB::table('cash_migrations')
        ->leftJoin('companies as company_from','company_from.id','=','cash_migrations.company_from')
        ->leftJoin('companies as company_to','company_to.id','=','cash_migrations.company_to')
        ->leftJoin('accounts as account_from','account_from.id','=','cash_migrations.cash_account_from')
        ->leftJoin('accounts as account_to','account_to.id','=','cash_migrations.cash_account_to')
        ->whereRaw($wr);

        if($request->draw == 1) {
            $item = $item->orderBy('cash_migrations.id', 'DESC');
        }

        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_request', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

        $item = $item->select(
            'cash_migrations.id',
            'cash_migrations.code',
            'cash_migrations.total',
            'cash_migrations.date_request',
            'cash_migrations.date_needed',
            'cash_migrations.status',
            'cash_migrations.created_at',
            'account_from.name as account_from',
            'account_to.name as account_to',
            'company_from.name as company_from',
            'company_to.name as company_to'
        );

        return $item;
    }

    public static function cek_giro_query(Request $request)
    {
        $wr="1=1";
        if (isset($request->penerima_id))
            $wr.=" AND cek_giros.penerima_id = $request->penerima_id";

        if (isset($request->penerbit_id))
            $wr.=" AND cek_giros.penerbit_id = $request->penerbit_id";

        if (isset($request->journal_status))
            $wr.=" AND journals.status = $request->journal_status";

        if($request->company_id)
            $wr.=" AND cek_giros.company_id = {$request->company_id}";

        if (auth()->user()->is_admin==0)
            $wr.=" AND cek_giros.company_id = ".auth()->user()->company_id;

        if($request->start_date_transaction)
            $wr .= ' AND cek_giros.date_transaction >= "' .dateDB($request->start_date_transaction). '"';

        if($request->end_date_transaction)
            $wr .= ' AND cek_giros.date_transaction <= "' .dateDB($request->end_date_transaction). '"';

        if($request->start_date_effective)
            $wr .= ' AND cek_giros.date_effective >= "' .dateDB($request->start_date_effective). '"';

        if($request->end_date_effective)
            $wr .= ' AND cek_giros.date_effective <= "' .dateDB($request->end_date_effective). '"';

        if($request->start_amount)
            $wr .= " AND cek_giros.amount >= {$request->start_amount}";

        if($request->end_amount)
            $wr .= " AND cek_giros.amount <= {$request->end_amount}";

        $item = CekGiro::with('company','penerbit','penerima')->leftJoin('journals', 'journals.id', 'journal_id')->whereRaw($wr);

        return $item->select('cek_giros.*');
    }

    public static function bill_query(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND bills.company_id = ".auth()->user()->company_id;
        }
        $item=Bill::with('company','customer')->whereRaw($wr);

        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_request', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;

        $customer_id = $request->customer_id;
        $customer_id = $customer_id != null ? $customer_id : '';
        $item = $customer_id != '' ? $item->where('customer_id', $customer_id) : $item;

        $status = $request->status;
        $status = $status != null ? $status : '';
        $item = $status != '' ? $item->where(DB::raw('bills.status'), $status) : $item;

        $item = $item->select('bills.*');

        return $item;
    }

    public static function debt_query(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND debts.company_id = ".auth()->user()->company_id;
        }
        $item=Debt::with('company')->whereRaw($wr);

        $start_date = $request->start_date;
        $start_date = $start_date != null ? new DateTime($start_date) : '';
        $end_date = $request->end_date;
        $end_date = $end_date != null ? new DateTime($end_date) : '';
        $item = $start_date != '' && $end_date != '' ? $item->whereBetween('date_request', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where(DB::raw('debts.company_id'), $company_id) : $item;

        $status = $request->status;
        $status = $status != null ? $status : '';
        $item = $status != '' ? $item->where(DB::raw('status'), $status) : $item;
        $item->orderByRaw('date_request desc, id desc');
        return $item;
    }

    public static function debt_payment_query(Request $request)
    {
        $wr="(status = 2 OR status = 3)";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND debts.company_id = ".auth()->user()->company_id;
        }
        $item=Debt::with('company')->whereRaw($wr);

        $start_date_request = $request->start_date_request;
        $start_date_request = $start_date_request != null ? new DateTime($start_date_request) : '';
        $end_date_request = $request->end_date_request;
        $end_date_request = $end_date_request != null ? new DateTime($end_date_request) : '';
        $item = $start_date_request != '' && $end_date_request != '' ? $item->whereBetween('date_request', [$start_date_request->format('Y-m-d'), $end_date_request->format('Y-m-d')]) : $item;

        $start_date_receive = $request->start_date_receive;
        $start_date_receive = $start_date_receive != null ? new DateTime($start_date_receive) : '';
        $end_date_receive = $request->end_date_receive;
        $end_date_receive = $end_date_receive != null ? new DateTime($end_date_receive) : '';
        $item = $start_date_receive != '' && $end_date_receive != '' ? $item->whereBetween('date_receive', [$start_date_receive->format('Y-m-d'), $end_date_receive->format('Y-m-d')]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where('company_id', $company_id) : $item;

        $status = $request->status;
        $status = $status != null ? $status : '';
        $item = $status != '' ? $item->where('status', $status) : $item;
        $item->orderByRaw('date_request desc, id desc');
        return $item->select('debts.*');
    }

    public static function um_supplier_query(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0) {
            $wr.=" AND um_suppliers.company_id = ".auth()->user()->company_id;
        }

        if($request->start_date)
            $wr .= " AND um_suppliers.date_transaction >= '". dateDB($request->start_date) ."'";

        if($request->end_date)
            $wr .= " AND um_suppliers.date_transaction <= '". dateDB($request->end_date) ."'";

        $item = UmSupplier::leftJoin('type_transactions','type_transactions.id','=','um_suppliers.type_transaction_id')
        ->leftJoin('contacts','contacts.id','=','um_suppliers.contact_id')
        ->leftJoin('companies','companies.id','=','um_suppliers.company_id')
        ->leftJoin('journals','journals.code','=','um_suppliers.code')
        ->where('type_transactions.slug','!=','saldoAwal')
        ->whereRaw($wr);

        $start_debet = $request->start_debet;
        $start_debet = $start_debet != null ? str_replace(',', '', $start_debet) : 0;
        $end_debet = $request->end_debet;
        $end_debet = $end_debet != null ? str_replace(',', '', $end_debet) : 0;
        $item = $end_debet != 0 ? $item->whereBetween('um_suppliers.debet', [$start_debet, $end_debet]) : $item;

        $start_credit = $request->start_credit;
        $start_credit = $start_credit != null ? str_replace(',', '', $start_credit) : 0;
        $end_credit = $request->end_credit;
        $end_credit = $end_credit != null ? str_replace(',', '', $end_credit) : 0;
        $item = $end_credit != 0 ? $item->whereBetween('um_suppliers.credit', [$start_credit, $end_credit]) : $item;

        $start_sisa = $request->start_sisa;
        $start_sisa = $start_sisa != null ? str_replace(',', '', $start_sisa) : 0;
        $end_sisa = $request->end_sisa;
        $end_sisa = $end_sisa != null ? str_replace(',', '', $end_sisa) : 0;
        $item = $end_sisa != 0 ? $item->whereBetween( DB::raw("(um_suppliers.debet-um_suppliers.credit)"), [$start_sisa, $end_sisa]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where('um_suppliers.company_id', $company_id) : $item;

        $supplier_id = $request->supplier_id;
        $supplier_id = $supplier_id != null ? $supplier_id : '';
        $item = $supplier_id != '' ? $item->where('um_suppliers.supplier_id', $supplier_id) : $item;

        $item = $item->select('um_suppliers.*','contacts.name as cname','companies.name as coname',DB::raw("um_suppliers.debet-um_suppliers.credit as sisa"), 'journals.status AS journal_status');
        return $item;
    }

    public static function um_customer_query(Request $request)
    {
        $wr="1=1";
        if (auth()->user()->is_admin==0)
            $wr .= " AND um_customers.company_id = ".auth()->user()->company_id;

        if($request->start_date)
            $wr .= " AND um_customers.date_transaction >= '". dateDB($request->start_date) ."'";

        if($request->end_date)
            $wr .= " AND um_customers.date_transaction <= '". dateDB($request->end_date) ."'";

        $item = UmCustomer::leftJoin('type_transactions','type_transactions.id','=','um_customers.type_transaction_id')
        ->join('um_customer_details', 'header_id', 'um_customers.id')
        ->leftJoin('contacts','contacts.id','=','um_customers.contact_id')
        ->leftJoin('companies','companies.id','=','um_customers.company_id')
        ->leftJoin('journals','journals.code','=','um_customers.code')
        ->where('type_transactions.slug','!=','saldoAwal')
        ->whereRaw($wr);

        $start_debet = $request->start_debet;
        $start_debet = $start_debet != null ? str_replace(',', '', $start_debet) : 0;
        $end_debet = $request->end_debet;
        $end_debet = $end_debet != null ? str_replace(',', '', $end_debet) : 0;
        $item = $end_debet != 0 ? $item->whereBetween('um_customers.debet', [$start_debet, $end_debet]) : $item;

        $start_credit = $request->start_credit;
        $start_credit = $start_credit != null ? str_replace(',', '', $start_credit) : 0;
        $end_credit = $request->end_credit;
        $end_credit = $end_credit != null ? str_replace(',', '', $end_credit) : 0;
        $item = $end_credit != 0 ? $item->whereBetween('um_customers.credit', [$start_credit, $end_credit]) : $item;

        $start_sisa = $request->start_sisa;
        $start_sisa = $start_sisa != null ? str_replace(',', '', $start_sisa) : 0;
        $end_sisa = $request->end_sisa;
        $end_sisa = $end_sisa != null ? str_replace(',', '', $end_sisa) : 0;
        $item = $end_sisa != 0 ? $item->whereBetween( DB::raw("um_customers.credit-um_customers.debet"), [$start_sisa, $end_sisa]) : $item;

        $company_id = $request->company_id;
        $company_id = $company_id != null ? $company_id : '';
        $item = $company_id != '' ? $item->where(DB::raw('um_customers.company_id'), $company_id) : $item;

        $customer_id = $request->customer_id;
        $customer_id = $customer_id != null ? $customer_id : '';
        $item = $customer_id != '' ? $item->where(DB::raw('contacts.id'), $customer_id) : $item;

        $item = $item->selectRaw('um_customers.*, contacts.name as cname, companies.name as coname, um_customers.credit-um_customers.debet as sisa, journals.status AS journal_status')
        ->groupBy('um_customers.id');

        return $item;
    }

    public function draft_list_hutang_datatable(Request $request)
    {
        $hutang = Payable::query($request->all());

        $resp =  DataTables::of($hutang)
        ->addColumn('action', function($hutang){
            $html="<a ng-show=\"roleList.includes('finance.credit.draft.detail')\" ui-sref='finance.draft_list_hutang.show({id:$hutang->id})' ><span class='fa fa-folder-o'  data-toggle='tooltip' title='Detail Data'></span></a>&nbsp;&nbsp;";

            return $html;
        })
        ->addColumn('sisa', function($hutang) {
            return formatNumber($hutang->sisa_hutang);
        })
        ->addColumn('umur', function($hutang) {
            return number_format($hutang->umur);
        })
        ->addColumn('status', function($hutang){
            $status=[
                1 => '<span class="label label-primary">Lunas</span>',
                2 => '<span class="label label-danger">Outstanding</span>',
                3 => '<span class="label label-success">Proses</span>',
            ];
            return $status[$hutang->status];
        })
        ->rawColumns(['action','status'])
        ->make(true);

        $raw = $resp->getData();
        $raw->sisa = Payable::getSisa($request->all());
        $resp->setData($raw);

        return $resp;
    }
}
