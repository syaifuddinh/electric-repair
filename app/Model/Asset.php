<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function asset_group()
  {
      return $this->belongsTo('App\Model\AssetGroup','asset_group_id','id');
  }
  public function account_accumulation()
  {
      return $this->belongsTo('App\Model\Account','account_accumulation_id','id');
  }
  public function account_depreciation()
  {
      return $this->belongsTo('App\Model\Account','account_depreciation_id','id');
  }
  public function account_asset()
  {
      return $this->belongsTo('App\Model\Account','account_asset_id','id');
  }

}
