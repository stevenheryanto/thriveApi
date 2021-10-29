<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User_comment extends Model
{
    use HasFactory;

    protected $table = 'user_comment';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
