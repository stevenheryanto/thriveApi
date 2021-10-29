<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class recognize_platform_hdr extends Model
{
    use HasFactory;
    protected $table = 'recognize_plaform_hdr';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
