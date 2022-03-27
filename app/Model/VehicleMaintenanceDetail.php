<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VehicleMaintenanceDetail extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['tipe_kegiatan_name'];

  public function header()
  {
      return $this->belongsTo('App\Model\VehicleMaintenance','header_id','id');
  }
  public function item()
  {
      return $this->belongsTo('App\Model\Item','item_id','id');
  }
  public function vehicle_maintenance_type()
  {
      return $this->belongsTo('App\Model\VehicleMaintenanceType','vehicle_maintenance_type_id','id');
  }
  public function getTipeKegiatanNameAttribute()
  {
    $stt=[
      1 => "Penggantian",
      2 => "Perbaikan",
      3 => "Pemeriksaan",
    ];
    return $stt[$this->attributes['tipe_kegiatan']];
  }

}
