<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['is_lunas'];

    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }
    public function customer()
    {
        return $this->belongsTo('App\Model\Contact','customer_id','id');
    }
    public function journal()
    {
        return $this->belongsTo('App\Model\Journal','journal_id','id');
    }

    public function receivable()
    {
        return $this->belongsTo('App\Model\Receivable', 'receivable_id');
    }

    public function find_receivable()
    {
        if(!is_null($this->receivable))
            return $this->receivable;
        
        return Receivable::where('relation_id', $this->id)->first();
    }

    public function getIsLunasAttribute()
    {
        $rec = $this->find_receivable();
        if(is_null($rec))
            return false;

        return $rec->is_lunas;
    }
}
