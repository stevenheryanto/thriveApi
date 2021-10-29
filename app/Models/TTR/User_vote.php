<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_vote extends Model
{
    use HasFactory;

    protected $table = 'user_vote';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';

    public function user()
    {
        return $this->belongsTo('App\Models\TTR\User');
    }

    public function user_post()
    {
        return $this->belongsTo('App\Models\TTR\User_post');
    }


}
