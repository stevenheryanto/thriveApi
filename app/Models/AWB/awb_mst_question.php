<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_mst_question extends Model
{
    use HasFactory;

    protected $table = 'awb_mst_question';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
