<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WorkOrderDraft extends Model
{
    protected $guarded=['id'];

    public function customer()
    {
        return $this->belongsTo('App\Model\Contact','customer_id','id');
    }
    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }
    public function user_create()
    {
        return $this->belongsTo('App\User','create_by','id');
    }

}
