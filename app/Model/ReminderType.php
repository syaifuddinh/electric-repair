<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class ReminderType extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['type_name'];

  public function getTypeNameAttribute()
  {
    $stt=[
      1 => 'Jam',
      2 => 'Hari',
      3 => 'Km',
    ];
    return $stt[$this->attributes['type']];
  }
}
