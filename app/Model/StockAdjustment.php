<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function creates()
  {
      return $this->belongsTo('App\User','create_by','id');
  }
  public function warehouse()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
  }

}
