<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class JournalDetail extends Model
{
    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo('App\Model\Account','account_id','id');
    }
    public function journal()
    {
        return $this->belongsTo('App\Model\Journal','header_id','id');
    }
}
