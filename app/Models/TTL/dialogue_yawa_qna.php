<?php

namespace App\Models\TTL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dialogue_yawa_qna extends Model
{
    use HasFactory;

    protected $table = 'dialogue_yawa_qna';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
