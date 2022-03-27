<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    protected $guarded = ['id'];

    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }
    public function from()
    {
        return $this->belongsTo('App\Model\City','city_from','id');
    }
    public function to()
    {
        return $this->belongsTo('App\Model\City','city_to','id');
    }
    public function details()
    {
        return $this->hasMany('App\Model\RouteCost','route_id','id');
    }
}
