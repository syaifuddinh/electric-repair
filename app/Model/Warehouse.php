<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
  protected $guarded = ['id'];

  public function type()
  {
      return $this->belongsTo('App\Model\WarehouseType','warehouse_type_id','id');
  }
  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
}
