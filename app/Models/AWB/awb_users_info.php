<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_users_info extends Model
{
    use HasFactory;

    protected $table = 'awb_users_info';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_created';
    protected $guarded = [];
}
