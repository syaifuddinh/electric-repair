<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UmCustomer extends Model
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
    public function umCustomerDetail()
    {
        return $this->hasOne('App\Model\UmCustomerDetail','header_id','id');
    }
    public function umCustomerPaids()
    {
        return $this->hasMany('App\Model\UmCustomerPaid','header_id','id');
    }
}
