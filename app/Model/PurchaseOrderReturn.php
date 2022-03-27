<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderReturn extends Model
{
    protected $guarded = ['id'];

    public function warehouse()
    {
        return $this->belongsTo('App\Model\Warehouse','warehouse_id','id');
    }
    public function po()
    {
        return $this->belongsTo('App\Model\PurchaseOrder','purchase_order_id','id');
    }

}
