<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_post extends Model
{
    use HasFactory;

    protected $table = 'user_post';
    const CREATED_AT = 'DATE_FORMAT(date_created,"%d %M %Y %H:%i:%S")';
    const UPDATED_AT = 'date_modified';

    public function user()
    {
        return $this->belongsTo('App\Models\TTR\User');
    }

    public function user_vote()
    {
        return $this->hasMany('App\Models\TTR\User_vote');
    }
}
