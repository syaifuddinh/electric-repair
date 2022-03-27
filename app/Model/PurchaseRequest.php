<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequest extends Model
{
  protected $guarded = ['id'];

public function user()
  {
      return $this->belongsTo('App\User','create_by','id');
  }
  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function supplier()
  {
      return $this->belongsTo('App\Model\Contact','supplier_id','id');
  }
  public function warehouse()
  {
      return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
  }
}
