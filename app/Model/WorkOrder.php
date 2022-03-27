<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class WorkOrder extends Model
{
  protected $guarded = ['id'];
  protected $hidden  = ['created_at', 'updated_at'];

  public function customer()
  {
      return $this->belongsTo('App\Model\Contact','customer_id','id');
  }

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }

  public function quotation()
  {
      return $this->belongsTo('App\Model\Quotation','quotation_id','id');
  }

  public function invoice_detail()
  {
      return $this->hasMany('App\Model\InvoiceDetail','work_order_id','id');
  }

  public function job_orders()
  {
      return $this->hasMany('App\Model\JobOrder','work_order_id','id');
  }

}
