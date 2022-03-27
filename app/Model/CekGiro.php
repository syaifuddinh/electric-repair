<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CekGiro extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

  public function penerbit()
  {
      return $this->belongsTo('App\Model\Contact','penerbit_id','id');
  }

  public function penerima()
  {
      return $this->belongsTo('App\Model\Contact','penerima_id','id');
  }

}
