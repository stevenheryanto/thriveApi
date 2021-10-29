<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class sw_mst_curriculum extends Model
{
    use HasFactory;

    protected $table = 'sw_mst_curriculum';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
