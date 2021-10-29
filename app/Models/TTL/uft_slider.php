<?php

namespace App\Models\TTL;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class uft_slider extends Model
{
    use HasFactory;

    protected $table = 'uft_slider';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
