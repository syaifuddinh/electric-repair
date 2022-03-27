<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VehicleDocument extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['type_name'];

  public function getTypeNameAttribute()
  {
    $stt=[
      1 => 'STNK',
      2 => 'SIUP',
      3 => 'KEUR',
      4 => 'KIM/IMK',
      5 => 'PERBAIKAN',
      6 => 'BPKB',
      7 => 'FOTO KENDARAAN',
      8 => 'LAIN - LAIN',
    ];
    return $stt[$this->attributes['type']];
  }


}
