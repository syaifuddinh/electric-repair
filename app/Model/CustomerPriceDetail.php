<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;

class CustomerPriceDetail extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at', 'header_id'];

    public function service()
    {
        return $this->belongsTo('App\Model\Service','service_id','id');
    }
}
