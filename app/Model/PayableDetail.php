<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class PayableDetail extends Model
{
    //
    protected $guarded = ['id'];
    public function journalDetail()
    {
        return $this->belongsTo('App\Model\JournalDetail','journal_id','id');
    }
}
