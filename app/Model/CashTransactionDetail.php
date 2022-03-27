<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CashTransactionDetail extends Model
{
  protected $guarded = ['id'];

  public function account()
  {
      return $this->belongsTo('App\Model\Account','account_id','id');
  }

  public function contact()
  {
      return $this->belongsTo('App\Model\Contact','contact_id','id');
  }

}
