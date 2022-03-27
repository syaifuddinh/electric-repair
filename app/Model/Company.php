<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $guarded = ['id'];
    protected $hidden = ['created_at', 'updated_at', 'header_id'];

    public function area()
    {
        return $this->belongsTo('App\Model\Area', 'area_id', 'id');
    }
    public function city()
    {
        return $this->belongsTo('App\Model\City', 'city_id', 'id');
    }
    public function receivables()
    {
        return $this->hasMany('App\Model\Receivable','company_id','id');
    }
    public function payables()
    {
        return $this->hasMany('App\Model\Payable','company_id','id');
    }
}
