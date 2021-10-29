<?php

namespace App\Models\TTL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class uft_config extends Model
{
    use HasFactory;

    protected $table = 'uft_config';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
