<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    protected $guarded = ['id'];

    public function country()
    {
        return $this->belongsTo('App\Model\Country','country_id','id');
    }
}
