<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ManifestCost extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['status_name'];

  public function header()
  {
      return $this->belongsTo('App\Model\Manifest','header_id','id');
  }
  public function cost_type()
  {
      return $this->belongsTo('App\Model\CostType','cost_type_id','id');
  }
  public function vendor()
  {
      return $this->belongsTo('App\Model\Contact','vendor_id','id');
  }
  public function getStatusNameAttribute($value)
  {
    try {
      $stt=[
        1 => "Biaya Belum Diajukan",
        2 => "Biaya Diajukan",
        3 => "Biaya Disetujui",
        4 => "Biaya Ditolak",
        5 => "Biaya Diposting",
        6 => "Biaya Direvisi",
      ];
      return $stt[$this->attributes['status']];
    } catch (\Exception $e) {
      return null;
    }
  }

}
