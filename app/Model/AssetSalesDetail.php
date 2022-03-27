<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AssetSalesDetail extends Model
{
    protected $guarded = ['id'];

    public function asset()
    {
        return $this->belongsTo('App\Model\Asset','asset_id','id');
    }
}
