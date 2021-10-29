<?php

namespace App\Models\Findtime;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ideation_user_comment extends Model
{
    use HasFactory;

    protected $table = 'ideation_user_comment';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
