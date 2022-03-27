<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class NotaDebet extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['jenis_name'];
  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function contact()
  {
      return $this->belongsTo('App\Model\Contact','contact_id','id');
  }
  public function payable()
  {
      return $this->belongsTo('App\Model\Payable','payable_id','id');
  }
  public function getJenisNameAttribute($value)
  {
    $stt=[
      1 => "Debet",
      2 => "Credit",
    ];
    return $stt[$this->attributes['jenis']];
  }
}
