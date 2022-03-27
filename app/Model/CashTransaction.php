<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CashTransaction extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['jenis_name','type_name'];

    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }

    public function CashTransactionDetail()
    {
        return $this->hasMany('App\Model\CashTransactionDetail','header_id','id');
    }

    public function type_transaction()
    {
        return $this->belongsTo('App\Model\TypeTransaction','type_transaction_id','id');
    }

    public function account()
    {
        return $this->belongsTo('App\Model\Account','account_id','id');
    }

    public function getTypeNameAttribute()
    {
        $stt=[
            1=>'Kas',
            2=>'Bank',
        ];

        return $stt[$this->attributes['type']] ?? '';
    }

    public function getJenisNameAttribute()
    {
        $stt=[
            1=>'Masuk',
            2=>'Keluar',
        ];

        return $stt[$this->attributes['jenis']] ?? '';
    }

    public function couldBeApproved()
    {
        if(!$this->account_id)
            return false;

        foreach($this->CashTransactionDetail as $detail) {
            if(!$detail->account_id)
                return false;
        }

        return true;
    }

    public function createJurnal()
    {
        $transactionDetails = CashTransactionDetail::where('header_id', $this->id)->get();

        $jurnal = Journal::create([
            'company_id' => $this->company_id,
            'type_transaction_id' => $this->type_transaction_id,
            'date_transaction' => $this->date_transaction,
            'created_by' => auth()->id(),
            'code' => $this->code,
            'description' => $this->description,
            'debet' => $this->total,
            'credit' => $this->total
        ]);

        JournalDetail::create([
            'header_id' => $jurnal->id,
            'account_id' => $this->account_id,
            'debet' => ($this->jenis == 1) ? $this->total : 0,
            'credit' => ($this->jenis != 1) ? $this->total : 0,
        ]);
        
        foreach($transactionDetails as $trDetail) {
            JournalDetail::create([
                'header_id' => $jurnal->id,
                'account_id' => $trDetail->account_id,
                'debet' => ($trDetail->jenis == 1) ? $trDetail->amount : 0,
                'credit' => ($trDetail->jenis != 1) ? $trDetail->amount : 0
            ]);
        }

        $this->journal_id = $jurnal->id;

        return $jurnal;
    }
}
