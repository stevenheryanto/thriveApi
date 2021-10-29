<?php

namespace App\Models\TTT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetothink_rating extends Model
{
    use HasFactory;

    protected $table = 'timetothink_rating';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
