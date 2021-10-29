<?php

namespace App\Models\TTT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Users_function_mapping extends Model
{
    use HasFactory;

    protected $table = 'users_function_mapping';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
