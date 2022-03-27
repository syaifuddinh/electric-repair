<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
  protected $guarded = ['id'];
  protected $hidden = ['created_at', 'updated_at'];
  protected $appends = ['is_packet', 'is_packaging', 'is_handling', 'is_stuffing'];

  public static function boot() {
      parent::boot();

      static::creating(function(Service $service){
          if($service->service_type_id == 15) {
            $service->is_wh_rent=1;
          }
      });

      static::updating(function(Service $service){
          if($service->service_type_id == 15) {
            $service->is_wh_rent=1;
          }
      });
  }

  public function getIsPacketAttribute() {
      if( array_key_exists('id', $this->attributes) ) {
          if($this->combined_price != null) {
              return 1;
          }
      }

      return 0;
  }
  public function getIsPackagingAttribute() {
      if( array_key_exists('service_type_id', $this->attributes) ) {
          if($this->attributes['service_type_id'] == 14) {
              return 1;
          }
      }

      return 0;
  }

  public function getIsHandlingAttribute() {
      if( array_key_exists('service_type_id', $this->attributes) ) {
          if($this->attributes['service_type_id'] == 12) {
              return 1;
          }
      }

      return 0;
  }

  public function getIsStuffingAttribute() {
      if( array_key_exists('service_type_id', $this->attributes) ) {
          if($this->attributes['service_type_id'] == 13) {
              return 1;
          }
      }

      return 0;
  }
  
  public function service_type()
  {
      return $this->belongsTo('App\Model\ServiceType','service_type_id','id');
  }
  public function combined_price()
  {
      return $this->hasOne('App\Model\CombinedPrice');
  }
  public function service_group()
  {
      return $this->belongsTo('App\Model\ServiceGroup','service_group_id','id');
  }
  public function kpi_statuses()
  {
      return $this->hasMany('App\Model\KpiStatus','service_id','id');
  }
  public function account_sale()
  {
      return $this->belongsTo('App\Model\Account','account_sale_id','id');
  }
}
