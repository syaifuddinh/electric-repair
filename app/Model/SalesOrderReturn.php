<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class SalesOrderReturn extends Model
{
    protected $guarded = ['id'];
    public function so()
    {
        return $this->belongsTo('App\Model\SalesOrder','sales_order_id','id');
    }
    public function warehouse()
    {
        return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
    }

}
