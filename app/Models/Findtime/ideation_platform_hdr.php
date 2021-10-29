<?php

namespace App\Models\Findtime;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ideation_platform_hdr extends Model
{
    use HasFactory;
    protected $table = 'ideation_platform_hdr';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
