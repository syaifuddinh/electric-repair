<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payable extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['total_hutang', 'total_bayar', 'sisa_hutang', 'umur', 'status', 'kode_invoice'];

    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }

    public function contact()
    {
        return $this->belongsTo('App\Model\Contact','contact_id','id');
    }

    public function journal()
    {
        return $this->belongsTo('App\Model\Journal','journal_id','id');
    }

    public function payableDetails()
    {
        return $this->hasMany('App\Model\PayableDetail','header_id','id');
    }

    public function type_transaction()
    {
        return $this->belongsTo('App\Model\TypeTransaction','type_transaction_id','id');
    }

    public function getTotalHutangAttribute()
    {
        return $this->payableDetails->sum('credit');
    }

    public function getTotalBayarAttribute()
    {
        return $this->payableDetails->sum('debet');
    }

    public function getSisaHutangAttribute()
    {
        return max($this->total_hutang - $this->total_bayar, 0);
    }

    public function getUmurAttribute()
    {
        $now = Carbon::now();
        $dueDate = Carbon::parse($this->date_tempo);
        if($now->gt($dueDate))
            return $now->diffInDays($dueDate);
        
        return 0;
    }

    public function getStatusAttribute()
    {
        if($this->sisa_hutang == 0)
            return 1;
        
        if($this->umur > 0)
            return 2;
        
        return 3;
    }

    public function getKodeInvoiceAttribute()
    {
        $kode = '';
        foreach($this->payableDetails as $detail) {
            $_kode = $detail->code;
            if(!($detail->code) && $detail->type_transaction_id == 29) {
                $invoice = InvoiceVendor::find($detail->relation_id);
                if(is_null($invoice))
                    continue;
                $_kode = $invoice->code;
            }

            if(!empty($_kode)) {
                if(!empty($kode))
                    $_kode = ', ' . $_kode;
                
                $kode .= $_kode;
            }
        }

        return $kode;
    }

    /**
     * Pembuatan jurnal pembentukan hutang. Parameter details merupakan array
     * dengan isian array dengan key "jenis", "account_id", "value".
     * Key "jenis" berisi integer, 1 adalah debet, 2 kredit.
     * Key "account_id" berisi integer id account dari tabel.
     * Key "value" berisi nominal dengan tipe data double.
     *
     * @param [] $details
     * @return Journal
     */
    public function createJurnalPembentukan($details)
    {
        $contact = Contact::find($this->contact_id);
        $accountHutang = ($contact->akunHutang) ?? AccountDefault::find(1)->hutang;

        $jurnal = Journal::create([
            'company_id' => $this->company_id,
            'type_transaction_id' => $this->type_transaction_id,
            'date_transaction' => $this->date_transaction,
            'created_by' => auth()->id(),
            'code' => $this->code,
            'description' => $this->description,
            'debet' => $this->credit,
            'credit' => $this->credit
        ]);

        JournalDetail::create([
            'header_id' => $jurnal->id,
            'account_id' => $accountHutang,
            'description' => $this->description,
            'debet' => 0,
            'credit' => $this->credit,
        ]);

        foreach($details as $detail) {
            JournalDetail::create([
                'header_id' => $jurnal->id,
                'account_id' => $detail['account_id'],
                'description' => $this->description,
                'debet' => ($detail['jenis'] == 1) ? $detail['value'] : 0,
                'credit' => ($detail['jenis'] != 1) ? $detail['value'] : 0
            ]);
        }

        $this->journal_id = $jurnal->id;
        return $jurnal;
    }
}