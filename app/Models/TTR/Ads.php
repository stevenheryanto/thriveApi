<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ads extends Model
{
    use HasFactory;

    protected $table = 'ads';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
