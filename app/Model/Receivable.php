<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Receivable extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['is_lunas'];

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

    public function details()
    {
        return $this->hasMany('App\Model\ReceivableDetail','header_id','id');
    }

    public function created_by_user()
    {
        return $this->belongsTo('App\User','create_by','id');
    }

    public function getIsLunasAttribute()
    {
        $debet = $kredit = 0;

        foreach($this->details as $detail) {
            $debet += $detail->debet;
            $kredit += $detail->kredit;
        }

        if($kredit >= $debet)
            return true;
        
        return false;
    }

    public function type_transaction()
    {
        return $this->belongsTo('App\Model\TypeTransaction','type_transaction_id','id');
    }

    /**
     * Pembuatan jurnal pembentukan piutang. Parameter details merupakan array
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
        $accountPiutang = ($contact->akunPiutang) ?? AccountDefault::find(1)->piutang;

        $jurnal = Journal::create([
            'company_id' => $this->company_id,
            'type_transaction_id' => $this->type_transaction_id,
            'date_transaction' => $this->date_transaction,
            'created_by' => auth()->id(),
            'code' => $this->code,
            'description' => $this->description,
            'debet' => $this->debet,
            'credit' => $this->debet
        ]);

        JournalDetail::create([
            'header_id' => $jurnal->id,
            'account_id' => $accountPiutang,
            'description' => $this->description,
            'debet' => $this->debet,
            'credit' => 0,
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
