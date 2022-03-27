<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class DebtPayment extends Model
{
  protected $guarded = ['id'];

  public function cash_account()
  {
      return $this->belongsTo('App\Model\Account','cash_account_id','id');
  }
  public function cek_giro()
  {
      return $this->belongsTo('App\Model\CekGiro','cek_giro_id','id');
  }
}
