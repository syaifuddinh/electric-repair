<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['city_name'];
    protected $hidden = [
      'created_at',
      'updated_at'
    ];

    public function province()
    {
        return $this->belongsTo('App\Model\Province','province_id','id');
    }

    public function getCityNameAttribute()
    {
      $stt=[
        'kota' => 'Kota',
        'kabupaten' => 'Kab.',
      ];
      try {
        return $stt[$this->attributes['type']].' '.$this->attributes['name'];
      } catch (\Exception $e) {
        return null;
      }
    }
}
