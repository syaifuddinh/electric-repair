<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ServiceType extends Model
{
    protected $hidden = ['created_at', 'updated_at'];
    
    public function services()
    {
      return $this->hasMany('App\Model\Service','service_type_id','id');
    }
}
