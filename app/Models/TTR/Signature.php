<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Signature extends Model
{
    use HasFactory;

    protected $table = 'signature';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
