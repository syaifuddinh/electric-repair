<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AssetSales extends Model
{
    protected $guarded = ['id'];
    protected $table = 'asset_sales';

    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }

    public function costumer()
    {
        return $this->belongsTo('App\Model\Contact','costumer_id','id');
    }

    public function cash_account()
    {
        return $this->belongsTo('App\Model\Account','cash_account_id','id');
    }

    public function sales_account()
    {
        return $this->belongsTo('App\Model\Account','sales_account_id','id');
    }
}
