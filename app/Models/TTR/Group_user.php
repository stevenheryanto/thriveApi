<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group_user extends Model
{
    use HasFactory;

    protected $table = 'group_user';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';

    public function user()
    {
        return $this->belongsTo('App\Models\TTR\User');
    }
}
