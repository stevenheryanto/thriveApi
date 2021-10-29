<?php

namespace App\Models\TTT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetothink_platform_hdr extends Model
{
    use HasFactory;
    protected $table = 'timetothink_plaform_hdr';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
