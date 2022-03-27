<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Journal extends Model
{
    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }

    public function type_transaction()
    {
        return $this->belongsTo('App\Model\TypeTransaction','type_transaction_id','id');
    }

    public function details()
    {
        return $this->hasMany('App\Model\JournalDetail','header_id','id');
    }

}
