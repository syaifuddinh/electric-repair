<?php

namespace App\Http\Controllers\Operational;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Account;
use App\Model\Contact;
use App\Model\Company;
use App\Model\InvoiceVendor;
use App\Model\InvoiceVendorDetail;
use App\Model\Payable;
use App\Model\PayableDetail;
use App\Model\Journal;
use App\Model\JournalDetail;
use DB;
use Response;

class InvoiceVendorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data['customer']=DB::table('contacts')->where('is_vendor',1)->selectRaw('id,name')->get();
        $data['company']=companyAdmin(auth()->id());
        return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
      $data['account']=Account::with('parent')->where('is_base',0)->orderBy('code','asc')->get();
      $data['company']=companyAdmin(auth()->id());
      $data['taxes']=DB::table('taxes')->selectRaw('id,name,pemotong_pemungut,npwp,non_npwp')->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
      // dd($request);
      $request->validate([
        'company_id' => 'required',
        'date_invoice' => 'required',
        'due_date' => 'required',
        'date_receive' => 'required',
        'vendor_id' => 'required',
        'code' => 'required',
        'detail' => 'required|array'
      ]);
      DB::beginTransaction();
      $i=InvoiceVendor::create([
        'company_id' => $request->company_id,
        'vendor_id' => $request->vendor_id,
        'code' => $request->code,
        'description' => $request->description,
        'date_invoice' => dateDB($request->date_invoice),
        'date_receive' => dateDB($request->date_receive),
        'due_date' => dateDB($request->input('due_date')),
        'create_by' => auth()->id(),
        'status_approve' => 1,
        'total' => $request->total,
        'ppn' => $request->ppn,
        'subtotal' => $request->subtotal,
      ]);
      $sum=0;
      foreach ($request->detail as $key => $value) {
        $insertDetailId=DB::table('invoice_vendor_details')->insertGetId([
          'header_id' => $i->id,
          'reff_no' => $value['reff_no'],
          'diskon' => $value['diskon'],
          'total' => $value['total'],
          'description' => $value['description'],
          'manifest_cost_id' => $value['manifest_cost_id'],
          'job_order_cost_id' => $value['job_order_cost_id'],
          'ppn' => $value['ppn'],
          'total_origin' => $value['total_origin'],
        ]);
        foreach ($value['ppn_detail'] as $ppn) {
          DB::table('invoice_vendor_taxes')->insert([
            'header_id' => $i->id,
            'detail_id' => $insertDetailId,
            'tax_id' => $ppn['tax_id'],
            'amount' => $ppn['amount'],
          ]);
        }
      }
      DB::commit();

      return Response::json(null);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $data['item']=InvoiceVendor::with('vendor','company')->where('id', $id)->first();
      $data['detail']=InvoiceVendorDetail::where('header_id', $id)->get();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    public function abort_journal($id) {
        $invoiceVendor = InvoiceVendor::find($id);
        if($invoiceVendor->status_approve == 4) {
            return Response::json(['message' => 'Invoice tidak dapat dibatalkan karena sudah masuk pembayaran hutang'], 500);
        } else {
            DB::beginTransaction();
            $journal = Journal::find($invoiceVendor->journal_id);
            JournalDetail::whereHeaderId($journal->id)->delete();
            $payable = Payable::find($invoiceVendor->payable_id);
            PayableDetail::whereHeaderId($payable->id)->delete();

            $invoiceVendor->journal_id = null;
            $invoiceVendor->payable_id = null;
            $invoiceVendor->save();
            Journal::destroy($journal->id);
            Payable::destroy($payable->id);
            $invoiceVendor->status_approve = 0;
            $invoiceVendor->save();
            DB::commit();
        }
    }

    public function approve($id, $flag = '') {
      DB::beginTransaction();
      $default = DB::table('account_defaults')->first();
      $iv = InvoiceVendor::find($id);
      $supplier = Contact::find($iv->vendor_id);
      $akunHutang = $supplier->akun_kas_hutang;
      if(!$supplier->akun_kas_hutang) {
        $akunHutang = $default->hutang;
        if (!$default->hutang) {
          return Response::json(['message' => 'tidak ada akun hutang yang di setting pada default akun'],500);
        }
      }

      $detail = DB::table('invoice_vendor_details as ivd');
      $detail = $detail->leftJoin('job_order_costs as joc', 'joc.id','ivd.job_order_cost_id');
      $detail = $detail->leftJoin('manifest_costs as mc', 'mc.id','ivd.manifest_cost_id');
      $detail = $detail->leftJoin('cost_types as ct_jo', 'ct_jo.id','joc.cost_type_id');
      $detail = $detail->leftJoin('cost_types as ct_m', 'ct_m.id','mc.cost_type_id');
      $detail = $detail->where('ivd.header_id', $id);
      $detail = $detail->selectRaw('
        ivd.*,
        ifnull(ct_jo.name,ct_m.name) as name,
        ifnull(ct_jo.akun_uang_muka,ct_m.akun_uang_muka) as akun_uang_muka,
        ifnull(ct_jo.akun_kas_hutang,ct_m.akun_kas_hutang) as akun_kas_hutang,
        ivd.job_order_cost_id,
        ivd.manifest_cost_id
      ');
      $detail = $detail->get();
      $type_transactions = DB::table('type_transactions')->where('slug', 'invoiceVendor')->first();
      $invoice_vendor_transaction_id = $type_transactions->id;
      $j = Journal::create([
        'company_id' => $iv->company_id,
        'type_transaction_id' => $invoice_vendor_transaction_id,
        'created_by' => auth()->id(),
        'date_transaction' => $iv->date_receive,
        'code' => $iv->code,
        'description' => "Invoice Vendor No. ".$iv->code." (".$iv->description.")",
        'status' => $flag != 'post' ? 1 : 2,
      ]);

      $p = Payable::create([
          'company_id' => $iv->company_id,
          'contact_id' => $iv->vendor_id,
          'type_transaction_id' => $invoice_vendor_transaction_id,
          'created_by' => auth()->id(),
          'code' => $iv->code,
          'relation_id' => $iv->id,
          'date_transaction' => $iv->date_receive,
          'date_tempo' => $iv->due_date,
          'description' => $iv->description,
          'debet' => $iv->total,
          'credit' => $iv->total,
          'is_invoice' => 1,
          'journal_id' => $j->id
      ]);

      $credit_hutang = 0;
      foreach ($detail as $unit) {
        if(!$unit->akun_uang_muka) return response()->json(['message' => 'Tidak ada akun "Uang Muka" yang di setting pada jenis biaya: '.$unit->name],500);
        if(!$unit->akun_kas_hutang) return response()->json(['message' => 'Tidak ada akun "Hutang" yang di setting pada jenis biaya: '.$unit->name],500);

        if ($unit->job_order_cost_id) {
          DB::table('job_order_costs')->where('id', $unit->job_order_cost_id)->update([
            'is_invoice' => 1
          ]);
        } elseif ($unit->manifest_cost_id) {
          DB::table('manifest_costs')->where('id', $unit->manifest_cost_id)->update([
            'is_invoice' => 1
          ]);
        }
        $pd=PayableDetail::create([
          'header_id' => $p->id,
          'type_transaction_id' => $invoice_vendor_transaction_id,
          'relation_id' => $unit->id,
          'code' => $iv->code,
          'date_transaction' => $iv->date_receive,
          'debet' => 0,
          'credit' => $unit->total+$unit->ppn,
          'description' => $unit->reff_no,
          'journal_id' => $j->id
        ]);
        $credit_hutang += $unit->total+$unit->ppn;
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $unit->akun_uang_muka,
          'debet' => $unit->total+$unit->ppn,
          'credit' => 0,
          'description' => "Invoice Vendor : $iv->code - $unit->name"
        ]);
        JournalDetail::create([
          'header_id' => $j->id,
          'account_id' => $unit->akun_kas_hutang,
          'debet' => 0,
          'credit' => $unit->total+$unit->ppn,
          'description' => $iv->description
        ]);
        DB::table('invoice_vendor_details')->where('id', $unit->id)->update([
          'payable_detail_id' => $pd->id
        ]);
      }
      $iv->update([
        'status_approve' => 2,
        'journal_id' => $j->id,
        'payable_id' => $p->id
      ]);

      DB::commit();
      return Response::json([], 200, [], JSON_NUMERIC_CHECK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
      DB::beginTransaction();
      DB::delete("DELETE iv, ivd FROM invoice_vendors as iv LEFT JOIN invoice_vendor_details as ivd ON iv.id = ivd.header_id WHERE iv.id = {$id}");
      DB::commit();
      return response()->json(['message' => 'OK']);
    }

    public function get_jo_cost($id)
    {
      $data = DB::table('job_order_costs as joc');
      $data = $data->leftJoin('job_orders as jo','jo.id','joc.header_id');
      $data = $data->leftJoin('cost_types as ct','ct.id','joc.cost_type_id');
      $data = $data->select([
        'joc.id',
        'jo.code',
        'ct.name',
        'joc.total_price',
        'joc.description',
      ]);
      $data = $data->where('joc.id', $id)->first();
      return Response::json($data, 200, [], JSON_NUMERIC_CHECK);
    }
}
