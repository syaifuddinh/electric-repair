<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CashAdvance extends Model
{
  protected $guarded = ['id'];

  public function company()
  {
      return $this->belongsTo('App\Model\Company','company_id','id');
  }
  public function employee()
  {
      return $this->belongsTo('App\Model\Contact','employee_id','id');
  }
  public function account_cash()
  {
      return $this->belongsTo('App\Model\Account','account_cash_id','id');
  }
  public function account_kasbon()
  {
      return $this->belongsTo('App\Model\Account','account_advance_id','id');
  }
  public function creates()
  {
      return $this->belongsTo('App\User','create_by','id');
  }
  public function approves()
  {
      return $this->belongsTo('App\User','approve_by','id');
  }
  public function cancels()
  {
      return $this->belongsTo('App\User','cancel_by','id');
  }

    public function statuses()
    {
        return $this->hasMany('App\Model\CashAdvanceStatus', 'header_id');
    }

    public function statusAkhir()
    {
        return $this->hasOne('App\Model\CashAdvanceStatus', 'header_id')
            ->latest();
    }

    public function statusAktif() 
    {
        return $this::with('statuses')
            ->where('status', 3)
            ->first();
    }

    public function statusSelesai() 
    {
        return $this::with('statuses')
            ->where('status', 5)
            ->first();
    }

    public function getStartDate()
    {
        if($this->statusAkhir()->get()->first()->status < 3)
            return '9999-12-31';
        
        $pengaktifan = CashAdvanceStatus::where('header_id', $this->id)
            ->where('status', 3)
            ->first();
        
        $dateAktif = Carbon::parse($pengaktifan->created_at());
        return $dateAktif->format('Y-m-d');
    }

    public function getEndDate()
    {
        $lastStatus = $this->statusAkhir()->get()->first()->status;
        if( $lastStatus < 5 )
            return '9999-12-31';
        
        $penonaktifan = CashAdvanceStatus::where('header_id', $this->id)
            ->where('status', 5)
            ->first();
        
        $dateNonaktif = Carbon::parse($penonaktifan->created_at());
        return $dateNonaktif->format('Y-m-d');
    }

    public function reapprovalsCount()
    {
        if($this->statusAkhir()->get()->first()->status < 3)
            return 0;
        
        $pengaktifan = CashAdvanceStatus::where('header_id', $this->id)
            ->where('status', 3)
            ->count();

        return max($pengaktifan - 1, 0);
    }

    public function latestReapproval()
    {
        if($this->statusAkhir() < 3)
            return null;

        return CashAdvanceStatus::where('header_id', $this->id)
            ->where('status', 3)
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    public function cash_transactions()
    {
        return $this->belongsTo('App\Model\CashTransaction', 'cash_transaction_id', 'id');
    }
}
