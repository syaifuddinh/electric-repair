<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AccountDefault extends Model
{
  protected $guarded = ['id'];

  public function getPiutang()
  {
    return $this->belongsTo('App\Model\Account','piutang','id');
  }
}
