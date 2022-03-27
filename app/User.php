<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'name', 'email', 'password',
    // ];
    protected $guarded = ['id'];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'pass_text', 'last_login_ip'
    ];

    public function company()
    {
        return $this->belongsTo('App\Model\Company','company_id','id');
    }
    public function contact()
    {
        return $this->belongsTo('App\Model\Contact','contact_id','id');
    }

    public function user_roles()
    {
        return $this->hasMany('App\Model\UserRole', 'user_id');
    }

    public function roles()
    {
        return $this->hasManyThrough('App\Model\Role', 'App\Model\UserRole', 'user_id', 'id', 'id','role_id');
    }

    public function hasRole($role_to_check)
    {
        if($this->is_admin) {
            return true;
        }
        foreach($this->roles as $role) {
            if($role_to_check == $role->slug)
                return true;
        }

        return false;
    }
}
