<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UmSupplier extends Model
{
    protected $guarded = ['id'];

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
    public function umSupplierDetail()
    {
        return $this->hasOne('App\Model\UmSupplierDetail','header_id','id');
    }
    public function umSupplierPaids()
    {
        return $this->hasMany('App\Model\UmSupplierPaid','header_id','id');
    }

}
