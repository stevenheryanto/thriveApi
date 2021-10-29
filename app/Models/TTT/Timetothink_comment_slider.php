<?php

namespace App\Models\TTT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetothink_comment_slider extends Model
{
    use HasFactory;

    protected $table = 'timetothink_comment_slider';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
