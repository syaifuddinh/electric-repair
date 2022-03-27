<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class UmSupplierPaid extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['type_name'];

  public function getTypeNameAttribute($value)
  {
    $stt=[
      1 => "Kas/Bank",
      2 => "Cek/Giro",
    ];
    return $stt[$this->attributes['type_paid']];
  }
}
