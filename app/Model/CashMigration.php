<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class CashMigration extends Model
{
  protected $guarded = ['id'];

  public static $statuses = [
        ["id" => 1, "name" => "Pengajuan"],
        ["id" => 2, "name" => "Persetujuan Keuangan"],
        ["id" => 3, "name" => "Persetujuan Direksi"],
        ["id" => 4, "name" => "Realisasi"],
        ["id" => 5, "name" => "Tolak"]
    ];

  public function company_fr()
  {
      return $this->belongsTo('App\Model\Company','company_from','id');
  }
  public function company_tr()
  {
      return $this->belongsTo('App\Model\Company','company_to','id');
  }
  public function account_from()
  {
      return $this->belongsTo('App\Model\Account','cash_account_from','id');
  }
  public function account_to()
  {
      return $this->belongsTo('App\Model\Account','cash_account_to','id');
  }
}
