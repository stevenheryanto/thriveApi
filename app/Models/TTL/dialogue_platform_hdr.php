<?php

namespace App\Models\TTL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class dialogue_platform_hdr extends Model
{
    use HasFactory;

    protected $table = 'dialogue_platform_hdr';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
