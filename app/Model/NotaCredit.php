<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class NotaCredit extends Model
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
  public function receivable()
  {
      return $this->belongsTo('App\Model\Receivable','receivable_id','id');
  }
  public function detailReceivable()
  {
      return $this->hasOne('App\Model\ReceivableDetail','relation_id','id');
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
