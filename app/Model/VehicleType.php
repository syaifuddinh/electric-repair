<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VehicleType extends Model
{
  protected $guarded = ['id'];
  protected $fillable = ['name', 'type'];
  protected $appends = ['type_name'];

  public function getTypeNameAttribute() {
        if(!array_key_exists('type', $this->attributes)) {
            return null;
        }
        if($this->attributes['type'] == 1) {
            return 'Transportasi';
        } else if($this->attributes['type'] == 2) {
            return 'Alat berat';
        } else if($this->attributes['type'] == 3) {
            return 'Forklift';
        }
  }
}
