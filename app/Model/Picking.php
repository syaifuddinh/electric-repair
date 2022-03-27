<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Picking extends Model
{
  protected $guarded = ['id'];

  public function warehouse()
	{
	    return $this->belongsTo('App\Model\Warehouse', 'warehouse_id', 'id');
	}
  public function company()
	{
	    return $this->belongsTo('App\Model\Company', 'company_id', 'id');
	}
  public function customer()
	{
	    return $this->belongsTo('App\Model\Contact', 'customer_id', 'id');
	}
  public function contact()
	{
	    return $this->belongsTo('App\Model\Contact', 'create_by', 'id');
	}
  public function staff()
	{
	    return $this->belongsTo('App\Model\Contact', 'staff_id', 'id');
	}
}
