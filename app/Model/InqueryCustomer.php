<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class InqueryCustomer extends Model
{
    protected $guarded = ['id'];

    public function customer()
    {
        return $this->belongsTo('App\Model\Contact','customer_id','id');
    }

}
