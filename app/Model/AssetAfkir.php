<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class AssetAfkir extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function asset()
  {
      return $this->belongsTo('App\Model\Asset','asset_id','id');
  }
  public function account_loss()
  {
      return $this->belongsTo('App\Model\Account','account_loss_id','id');
  }

}
