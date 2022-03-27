<?php

namespace App\Abstracts;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use App\Abstracts\Finance\Closing;
use Illuminate\Support\Facades\DB;

class Journal
{
    protected static $table = 'journals';

    /*
      Date : 05-03-2021
      Description : Memvalidasi data
      Developer : Didin
      Status : Create
    */
    public static function validate($id) {
        $dt = DB::table(self::$table)
        ->whereId($id)
        ->first();

        if(!$dt) {
            throw new Exception('Data not found');
        }
    }

    /*
      Date : 29-08-2021
      Description : Menampilkan detail
      Developer : Didin
      Status : Create
    */
    public static function show($id) {
        self::validate($id);
        $dt = DB::table(self::$table);
        $dt = $dt->where(self::$table . '.id', $id);
        $dt = $dt->first();

        return $dt;
    }

    /*
      Date : 29-08-2021
      Description : Membatalkan posting
      Developer : Didin
      Status : Create
    */
    public static function unposting($unposting_by, $unposting_reason, $id) {
        $dt = self::show($id);
        Closing::preventByDate($dt->date_transaction);
        if($dt->status == 2) {
            throw new Exception('Data was unposted');
        }

        DB::table(self::$table)->whereId($id)->update([
            'unposting_by' => $unposting_by,
            'unposting_reason' => $unposting_reason,
            'date_unposting' => Carbon::now(),
            'status' => 2
        ]);
    }


    /*
      Date : 29-08-2021
      Description : posting jurnal, untuk memasukkan jurnal ke pembukuan
      Developer : Didin
      Status : Create
    */
    public static function approvePost($posting_by, $item = []) {
        if (empty($item) || array_sum($item)<1) {
            throw new Exception('Tidak Ada Jurnal Dipilih!');
        }

        if(is_array($item)) {
            foreach ($item as $key => $value) {
                if (empty($value)) {
                  continue;
                }

                self::validateWasPosted($key);
                $dt = self::show($key);

                Closing::preventByDate($dt->date_transaction);


                DB::table(self::$table)->whereId($key)->update([
                    'status' => 3,
                    'posting_by' => $posting_by,
                    'date_posting' => date('Y-m-d'),
                ]);
            }
        }
    }

    /*
      Date : 29-08-2021
      Description : Menyetujui jurnal
      Developer : Didin
      Status : Create
    */
    public static function approve($posting_by, $item = []) {
        if (empty($item) || array_sum($item)<1) {
            throw new Exception('Tidak Ada Jurnal Dipilih!');
        }

        if(is_array($item)) {
            foreach ($item as $key => $value) {
                if (empty($value)) {
                  continue;
                }

                self::validateWasApproved($key);
                $dt = self::show($key);

                Closing::preventByDate($dt->date_transaction);


                DB::table(self::$table)->whereId($key)->update([
                    'status' => 2
                ]);
            }
        }
    }

    public static function validateWasPosted($id) {
        $dt = self::show($id);
        if($dt->status == 3) {
            throw new Exception('Journal was posted');
        }
    }


    public static function validateWasApproved($id) {
        $dt = self::show($id);
        if($dt->status == 2) {
            throw new Exception('Journal was approved');
        }
    }


    /*
      Date : 29-08-2021
      Description : posting jurnal, untuk memasukkan jurnal ke pembukuan
      Developer : Didin
      Status : Create
    */
    public static function posting($posting_by, $detail, $id) {
        self::validateWasPosted($id);
        $dt = self::show($id);
        Closing::preventByDate($dt->date_transaction);

        DB::table(self::$table)->whereId($id)->update([
            'posting_by' => $posting_by,
            'date_posting' => Carbon::now(),
            'status' => 3
        ]);

        if(is_array($detail)) {
            foreach ($detail as $value) {
                DB::table('journal_details')->whereId($value['id'])->update([
                    'cash_category_id' => $value['cash_category_id']
                ]);
            }
        }
    }

    /*
      Date : 29-08-2021
      Description : Membatalkan persetujuan jurnal
      Developer : Didin
      Status : Create
    */
    public static function undoApprove($id) {
        $dt = self::show($id);
        Closing::preventByDate($dt->date_transaction);

        DB::table(self::$table)->whereId($id)->update([
            'status' => 1
        ]);
    }

    /*
      Date : 29-08-2020
      Description : Menyimpan jurnal
      Developer : Didin
      Status : Create
    */
    /*
      Date : 12-07-2021
      Description : Tambah simpan jurnal klaim
      Developer : Hendra
      Status : Edit
    */
    public static function setJournal($type_transaction_id, $id) {
        $params = [];
        $params['date_transaction'] = Carbon::now()->format('Y-m-d');
        $params['type_transaction_id'] = $type_transaction_id;
        $createJournal = true;
        switch ($type_transaction_id) {
            case 14 :
                $purchaseOrder = DB::table('purchase_orders')
                ->whereId($id)
                ->first();
                $grandtotal = DB::table('purchase_order_details')
                ->whereHeaderId($id)
                ->sum('total');
                $account_default = DB::table('account_defaults')
                ->first();
                $purchaseOrderDetails = DB::table('purchase_order_details')
                ->join('items', 'items.id', 'purchase_order_details.item_id')
                ->where('purchase_order_details.header_id', $id)
                ->selectRaw('purchase_order_details.*, items.name AS item_name')
                ->get();
                if($account_default->pembelian == null) {
                    throw new Exception('Akun pembelian belum di-set pada setting default akun');
                }
                if($purchaseOrder->payment_type == 2) {
                    if($account_default->hutang == null) {
                        throw new Exception('Akun hutang belum di-set pada setting default akun');
                    }
                    $params['debet'] = $purchaseOrderDetails->map(function($p){
                        return $p->total;
                    })->toArray();
                    $params['credit'] = $purchaseOrderDetails->map(function($p){
                        return 0;
                    })->toArray();
                    $params['keterangan'] = $purchaseOrderDetails->map(function($p){
                        return 'Pembelian item ' . $p->item_name;
                    })->toArray();
                    $params['account_id'] = $purchaseOrderDetails->map(function($p) use($account_default){
                        $account = DB::table('accounts')
                        ->whereId($account_default->pembelian)
                        ->first();
                        return ['id' => $account_default->pembelian, 'type' => ['id' => $account->type_id]];
                    });
                    $account = DB::table('accounts')
                    ->whereId($account_default->hutang)
                    ->first();
                    $params['account_id'][] = ['id' => $account_default->hutang, 'type' => ['id' => $account->type_id]];
                    $params['credit'][] =  $grandtotal;
                    $params['debet'][] =  0;
                    $params['company_id'] = $purchaseOrder->company_id;
                    $params['description'] = 'Purchase order ' . $purchaseOrder->code;
                    $params['company_id'] = $purchaseOrder->company_id;
                } else {
                    $createJournal = false;
                    $cashTransaction = new \App\Http\Controllers\Finance\CashTransactionController();
                    $cashParams = [];
                    $cashAccount = DB::table('accounts')
                    ->where('no_cash_bank', '>', 0)
                    ->select('id')
                    ->first();
                    if(!$cashAccount) {
                        throw new Exception('Cash account is not exist, please check setting account');
                    }
                    $cashParams['date_transaction'] = Carbon::now()->format('Y-m-d');
                    $cashParams['company_id'] = $purchaseOrder->company_id;
                    $cashParams['cash_bank'] = $cashAccount->id;
                    $cashParams['type'] = 1;
                    $cashParams['jenis'] = 1;
                    $cashParams['kasbon_id'] = 0;
                    $cashParams['account_id'] = $account_default->pembelian;
                    $cashParams['description'] = 'Purchase order - ' . $purchaseOrder->code;
                    $cashParams['detail'] = $purchaseOrderDetails->map(function($p) use($account_default){
                        $params = ['jenis' => 1, 'amount' => $p->total, 'account_id' => $account_default->pembelian, 'description' => 'Purchase order item ' . $p->item_name, 'file' => ''];
                        return $params;
                    });

                    $cashTransaction->store(new Request($cashParams));
                    $newCashTransaction = DB::table('cash_transactions')
                    ->orderBy('id', 'desc')
                    ->first();

                    DB::table('purchase_orders')
                    ->whereId($id)
                    ->update([
                        'cash_transaction_id' => $newCashTransaction->id,
                    ]);
                }
            break;

            case 16 :
                $receipt = DB::table('receipts')
                ->join('receipt_lists', 'receipt_lists.header_id', 'receipts.id')
                ->where('receipt_lists.id', $id)
                ->select('receipts.*')
                ->first();
                $grandtotal = DB::table('receipt_list_details')
                ->whereHeaderId($id)
                ->sum('total_price');
                $account_default = DB::table('account_defaults')
                ->first();
                $receiptDetails = DB::table('receipt_list_details')
                ->join('items', 'items.id', 'receipt_list_details.item_id')
                ->where('receipt_list_details.header_id', $id)
                ->selectRaw('receipt_list_details.*, items.name AS item_name, items.account_id')
                ->get();
                if($account_default->pembelian == null) {
                    throw new Exception('Akun pembelian belum di-set pada setting default akun');
                }
                $params['account_id'] = $receiptDetails->map(function($p) use($account_default){
                    if(!$p->account_id) {
                        $account_id = $account_default->inventory;
                        if(!$account_id) {
                            throw new Exception('Akun pada item ' . $p->item_name . ' atau akun inventory pada setting belum diisi');
                        }
                    } else {
                        $account_id = $p->account_id;
                    }
                    $account = DB::table('accounts')
                    ->whereId($account_id)
                    ->first();

                    return ['id' => $account_id, 'type' => ['id' => $account->type_id]];
                });
                $params['debet'] = $receiptDetails->map(function($p){
                    return $p->total_price;
                })->toArray();
                $params['credit'] = $receiptDetails->map(function($p){
                    return 0;
                })->toArray();
                $params['credit'][] =  $grandtotal;
                $params['debet'][] =  0;
                $account = DB::table('accounts')
                ->whereId($account_default->pembelian)
                ->first();
                $params['account_id'][] = ['id' => $account_default->pembelian, 'type' => ['id' => $account->type_id]];
                $params['keterangan'] = $receiptDetails->map(function($p){
                    return 'Penerimaan barang ' . $p->item_name;
                })->toArray();
                $params['company_id'] = $receipt->company_id;
                $params['description'] = 'Penerimaan barang ' . $receipt->code;
                
            break;

            case 122 :
                $account_default = DB::table('account_defaults')
                                        ->first();
                if(!$account_default->biaya_klaim) {
                    throw new Exception('Biaya klaim harus di-setting terlebih dahulu');
                }
                $claim = DB::table('claims')
                            ->join('contacts', 'contacts.id', 'claims.collectible_id')
                            ->where('claims.id', $id)
                            ->select('claims.id', 'claims.code', 'contacts.akun_piutang', 'claim_type', 'claims.vendor_id', 'claims.driver_id', 'contacts.name AS collectible_name', 'claims.company_id', 'claims.collectible_id')
                            ->first();
                $akun_piutang = $claim->akun_piutang;
                if(!$claim->akun_piutang) {
                    if(!$account_default->piutang) {
                        throw new Exception('Akun piutang harus di-setting terlebih dahulu');
                    }
                    $akun_piutang = $account_default->piutang;
                }

                if($claim->claim_type == 1) {
                    $granted = $claim->driver_id;
                } else {
                    $granted = $claim->vendor_id;
                }
                $suspect = DB::table('contacts')
                            ->whereId($granted)
                            ->first();
                $akun_hutang = $suspect->akun_hutang;
                if(!$suspect->akun_hutang) {
                    if(!$account_default->hutang) {
                        throw new Exception('Akun hutang harus di-setting terlebih dahulu');
                    }
                    $akun_hutang = $account_default->hutang;
                }
                
                $hutang = DB::table('claim_details')
                            ->whereHeaderId($id)
                            ->sum('total_price');
                $piutang = DB::table('claim_details')
                            ->whereHeaderId($id)
                            ->sum('claim_total_price');
                $left = $hutang - $piutang;

                $src = DB::table('accounts')
                        ->whereId($account_default->biaya_klaim)
                        ->first();
                $params['account_id'] = [];
                $params['account_id'][] = [
                    'id' => $account_default->biaya_klaim,
                    'type' => [
                        'id' => $src->type_id
                    ]
                ];

                $src = DB::table('accounts')
                        ->whereId($account_default->piutang)
                        ->first();
                $params['account_id'][] = [
                    'id' => $account_default->piutang,
                    'type' => [
                        'id' => $src->type_id
                    ]
                ];

                $src = DB::table('accounts')
                        ->whereId($account_default->hutang)
                        ->first();
                $params['account_id'][] = [
                    'id' => $account_default->hutang,
                    'type' => [
                        'id' => $src->type_id
                    ]
                ];

                $params['debet'] = [$left, $piutang, 0];
                $params['credit'] = [0, 0, $hutang];
                $params['keterangan'] = [
                    'Biaya klaim terhadap transaksi ' . $claim->code,
                    'Piutang terhadap ' . $suspect->name,
                    'Hutang terhadap ' . $claim->collectible_name
                ];
                $params['company_id'] = $claim->company_id;
                $params['description'] = 'Klaim -  ' . $claim->code;
            break;
        }

        if($createJournal == true) {
            $journal = new \App\Http\Controllers\Finance\JournalController();
            $journal_id = $journal->save(new Request($params));
            if($type_transaction_id == 14) {
                DB::table('purchase_orders')
                ->whereId($id)
                ->update(['journal_id' => $journal_id]);
                $params = [];
                $params['company_id'] = $purchaseOrder->company_id;
                $params['contact_id'] = $purchaseOrder->supplier_id;
                $params['type_transaction_id'] = $type_transaction_id;
                $params['journal_id'] = $journal_id;
                $params['relation_id'] = $id;
                $params['created_by'] = auth()->user()->id;
                $params['code'] = $purchaseOrder->code;
                $params['date_transaction'] = Carbon::now()->format('Y-m-d');
                $params['created_at'] = Carbon::now()->format('Y-m-d');
                $supplier = DB::table('contacts')
                ->whereId($purchaseOrder->supplier_id)
                ->select('term_of_payment')
                ->first();
                $tempo = $supplier->term_of_payment ?? 1;
                $params['date_tempo'] = Carbon::now()->addDays($tempo)->format('Y-m-d');
                $params['credit'] = $grandtotal;
                $params['debet'] = $grandtotal;
                $params['description'] = 'Purchase order - ' . $purchaseOrder->code;
                $payable_id = DB::table('payables')
                ->insertGetId($params);

                $params = [];
                foreach ($purchaseOrderDetails as $item) {
                    $detail = [];
                    $detail['header_id'] = $payable_id;
                    $detail['type_transaction_id'] = $type_transaction_id;
                    $detail['journal_id'] = $journal_id;
                    $detail['created_at'] = Carbon::now()->format('Y-m-d');
                    $detail['description'] = 'Purchase order - ' . $purchaseOrder->code . ' - atas barang ' . $item->item_name;
                    $detail['code'] = $purchaseOrder->code;
                    $detail['debet'] = 0;
                    $detail['credit'] = $item->total;
                    $params[] = $detail;
                }

                DB::table('payable_details')
                ->insert($params);
                DB::table('purchase_orders')
                ->whereId($id)
                ->update([
                    'payable_id' => $payable_id
                ]);
            } else if($type_transaction_id == 122) {
                DB::table('claims')
                    ->whereId($id)
                    ->update(['journal_id' => $journal_id]);
                // Generate hutang
                $params = [];
                $params['company_id'] = $claim->company_id;
                $params['contact_id'] = $claim->collectible_id;
                $params['type_transaction_id'] = $type_transaction_id;
                $params['journal_id'] = $journal_id;
                $params['relation_id'] = $id;
                $params['created_by'] = auth()->user()->id;
                $params['code'] = $claim->code;
                $params['date_transaction'] = Carbon::now()->format('Y-m-d');
                $params['created_at'] = Carbon::now()->format('Y-m-d');
                $supplier = DB::table('contacts')
                                ->whereId($claim->collectible_id)
                                ->select('term_of_payment')
                                ->first();
                $tempo = $supplier->term_of_payment ?? 1;
                $params['date_tempo'] = Carbon::now()->addDays($tempo)->format('Y-m-d');
                $params['credit'] = $hutang;
                $params['debet'] = 0;
                $params['description'] = 'Klaim - ' . $claim->code;
                $payable_id = DB::table('payables')
                                ->insertGetId($params);

                $params = [];
                $claimDetails  = DB::table('claim_details')
                                ->join('commodities', 'commodities.id', 'claim_details.commodity_id')
                                ->whereHeaderId($id)
                                ->select('commodities.name AS commodity_name', 'claim_details.total_price', 'claim_details.claim_total_price')
                                ->get();
                foreach ($claimDetails as $item) {
                    $detail = [];
                    $detail['header_id'] = $payable_id;
                    $detail['type_transaction_id'] = $type_transaction_id;
                    $detail['journal_id'] = $journal_id;
                    $detail['created_at'] = Carbon::now()->format('Y-m-d');
                    $detail['description'] = 'Klaim - ' . $claim->code . ' - atas barang ' . $item->commodity_name;
                    $detail['code'] = $claim->code;
                    $detail['debet'] = 0;
                    $detail['credit'] = $item->total_price;
                    $params[] = $detail;
                }

                DB::table('payable_details')
                    ->insert($params);
                DB::table('claims')
                    ->whereId($id)
                    ->update([
                        'payable_id' => $payable_id
                    ]);
                // Generate piutang
                $params = [];
                $params['company_id'] = $claim->company_id;
                $params['contact_id'] = $granted;
                $params['type_transaction_id'] = $type_transaction_id;
                $params['journal_id'] = $journal_id;
                $params['relation_id'] = $id;
                $params['created_by'] = auth()->user()->id;
                $params['code'] = $claim->code;
                $params['date_transaction'] = Carbon::now()->format('Y-m-d');
                $params['created_at'] = Carbon::now()->format('Y-m-d');
                $supplier = DB::table('contacts')
                            ->whereId($granted)
                            ->select('term_of_payment')
                            ->first();
                $tempo = $supplier->term_of_payment ?? 1;
                $params['date_tempo'] = Carbon::now()->addDays($tempo)->format('Y-m-d');
                $params['debet'] = $hutang;
                $params['credit'] = 0;
                $params['description'] = 'Klaim - ' . $claim->code;
                $receivable_id = DB::table('receivables')
                                    ->insertGetId($params);

                $params = [];
                foreach ($claimDetails as $item) {
                    $detail = [];
                    $detail['header_id'] = $receivable_id;
                    $detail['type_transaction_id'] = $type_transaction_id;
                    $detail['journal_id'] = $journal_id;
                    $detail['created_at'] = Carbon::now()->format('Y-m-d');
                    $detail['description'] = 'Klaim - ' . $claim->code . ' - atas barang ' . $item->commodity_name;
                    $detail['code'] = $claim->code;
                    $detail['credit'] = 0;
                    $detail['debet'] = $item->claim_total_price;
                    $params[] = $detail;
                }

                DB::table('receivable_details')
                    ->insert($params);
                DB::table('claims')
                    ->whereId($id)
                    ->update([
                        'receivable_id' => $receivable_id
                    ]);
            }
        }
    }

    /*
      Date : 29-08-2020
      Description : Hapus data
      Developer : Didin
      Status : Create
    */
    public static function destroy($id) {
        self::validateWasPosted($id);
        self::validateWasApproved($id);
        $dt = self::show($id);
        Closing::preventByDate($dt->date_transaction);
        DB::table(self::$table)->whereId($id)->delete();
    }
}
