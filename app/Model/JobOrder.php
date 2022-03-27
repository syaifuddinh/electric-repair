<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class JobOrder extends Model
{
  protected $guarded = ['id'];
  protected $appends = ['is_tarif_umum', 'is_tarif_kontrak'];

  public function getIsTarifUmumAttribute() {
      if( array_key_exists('quotation_detail_id', $this->attributes) ) {
          if($this->attributes['quotation_detail_id'] == null) {
              return 1;
          }
      }

      return 0;
  }


  public function getIsTarifKontrakAttribute() {
      if( array_key_exists('quotation_detail_id', $this->attributes) ) {
          if($this->attributes['quotation_detail_id'] != null) {
              return 1;
          }
      }

      return 0;
  }

  public function collectible()
  {
      return $this->belongsTo('App\Model\Contact','collectible_id','id');
  }
  public function detail()
  {
      return $this->hasMany('App\Model\JobOrderDetail','header_id','id');
  }
  public function invoice_detail()
  {
      return $this->hasMany('App\Model\InvoiceDetail','job_order_id','id');
  }
  public function invoice_jual()
  {
      return $this->hasOne('App\Model\InvoiceDetail','job_order_id','id');
  }
  public function customer()
  {
      return $this->belongsTo('App\Model\Contact','customer_id','id');
  }
  public function work_order()
  {
      return $this->belongsTo('App\Model\WorkOrder','work_order_id','id');
  }
  public function work_order_detail()
  {
      return $this->belongsTo('App\Model\WorkOrderDetail','work_order_detail_id','id');
  }
  public function service()
  {
      return $this->belongsTo('App\Model\Service','service_id','id');
  }
  public function service_type()
  {
      return $this->belongsTo('App\Model\ServiceType','service_type_id','id');
  }
  public function trayek()
  {
      return $this->belongsTo('App\Model\Route','route_id','id');
  }
  public function moda()
  {
      return $this->belongsTo('App\Model\Moda','moda_id','id');
  }
  public function commodity()
  {
      return $this->belongsTo('App\Model\Commodity','commodity_id','id');
  }
  public function kpi_status()
  {
      return $this->belongsTo('App\Model\KpiStatus','kpi_id','id');
  }
  public function sender()
  {
      return $this->belongsTo('App\Model\Contact','sender_id','id');
  }
  public function receiver()
  {
      return $this->belongsTo('App\Model\Contact','receiver_id','id');
  }
  public function vehicle_type()
  {
      return $this->belongsTo('App\Model\VehicleType','vehicle_type_id','id');
  }
  public function container_type()
  {
      return $this->belongsTo('App\Model\ContainerType','container_type_id','id');
  }
  public function piece()
  {
      return $this->belongsTo('App\Model\Piece','piece_id','id');
  }
  public function quotation()
  {
      return $this->belongsTo('App\Model\Quotation','quotation_id','id');
  }
  public function sales_order()
  {
      return $this->hasOne('App\Model\SalesOrder','job_order_id','id');
  }
}
