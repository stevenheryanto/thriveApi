<?php

namespace App\Models\Menu;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    //public $timestamps = false;
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';

    public function group_user()
    {
        return $this->hasOne('App\Models\TTR\Group_user');
    }

    public function user_post()
    {
        return $this->hasMany('App\Models\TTR\User_post');
    }

    public function user_vote()
    {
        return $this->hasMany('App\Models\TTR\User_vote');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'account',
        'name',
        'full_name',
        'email',
        'title',
        'business_unit',
        'directorate',
        'division_p',
        'division_q',
        'department',
        'status_active',
        'sycn_date',
        'user_modified',
        'date_modified',
        'last_login',
        'profile_picture',
        'status_enable',
        'first_login',
        'dialogue_last_access',
        'flag_temporary',
        'validity_start_date',
        'validity_end_date',
        'user_created',
        'date_created',
        'employee_id',
        'uft_last_access',
        'supervisor_id',
        'employee_status',
        'landingpage_first_login',
        'landingpage_last_access',
        'timetolisten_first_login',
        'timetothink_first_login',
        'timetothink_last_access',
        'awb_first_login',
        'awb_last_access',
        'ffwd_first_login',
        'ffwd_last_access',
        'timetorecognition_first_login',
        'timetorecognition_last_access',
        'Country',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        // 'password',
        // 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        // 'email_verified_at' => 'datetime',
        'id' => 'string',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function getJWTCustomClaims()
    {
        return [];
    }


}
