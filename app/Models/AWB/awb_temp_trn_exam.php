<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_temp_trn_exam extends Model
{
    use HasFactory;

    protected $table = 'awb_temp_trn_exam';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
