<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class JournalFavoriteDetail extends Model
{
    protected $guarded = ['id'];

    public function account()
    {
        return $this->belongsTo('App\Model\Account','account_id','id');
    }
}
