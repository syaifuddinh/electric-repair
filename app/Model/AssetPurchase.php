<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AssetPurchase extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function supplier()
  {
      return $this->belongsTo('App\Model\Contact','supplier_id','id');
  }
  public function cash_account()
  {
      return $this->belongsTo('App\Model\Account','cash_account_id','id');
  }
}
