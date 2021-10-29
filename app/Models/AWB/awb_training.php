<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_training extends Model
{
    use HasFactory;

    protected $table = 'awb_training';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
