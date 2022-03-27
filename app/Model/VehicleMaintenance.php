<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VehicleMaintenance extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['internal_eksternal','status_name_html'];

  public function vendor()
  {
      return $this->belongsTo('App\Model\Contact','vendor_id','id');
  }

  public function getInternalEksternalAttribute()
  {
    $stt=[
      1 => "Internal",
      0 => "Eksternal",
    ];
    return $stt[$this->attributes['is_internal']];
  }
  public function getStatusNameHtmlAttribute()
  {
    $stt=[
      2 => '<span class="badge badge-primary">Pengajuan</span>',
      3 => '<span class="badge badge-warning">Rencana</span>',
      4 => '<span class="badge badge-info">Perawatan</span>',
      5 => '<span class="badge badge-success">Selesai</span>',
    ];
    return $stt[$this->attributes['status']];
  }
}
