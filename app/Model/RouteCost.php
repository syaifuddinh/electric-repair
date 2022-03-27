<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RouteCost extends Model
{
    protected $guarded = ['id'];

    public function commodity()
    {
        return $this->belongsTo('App\Model\Commodity','commodity_id','id');
    }
    public function vehicle_type()
    {
        return $this->belongsTo('App\Model\VehicleType','vehicle_type_id','id');
    }
    public function container_type()
    {
        return $this->belongsTo('App\Model\ContainerType','container_type_id','id');
    }
    public function details()
    {
      return $this->hasMany('App\Model\RouteCostDetail','header_id','id');
    }
    public function trayek()
    {
        return $this->belongsTo('App\Model\Route','route_id','id');
    }
}
