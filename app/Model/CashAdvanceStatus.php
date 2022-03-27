<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CashAdvanceStatus extends Model
{
    protected $guarded = ['id'];

    public function cashAdvance()
    {
        return $this->belongsTo('App\Model\CashAdvance','header_id','id');
    }

    public function user()
    {
        return $this->belongsTo('App\User','user_id','id');
    }
}
