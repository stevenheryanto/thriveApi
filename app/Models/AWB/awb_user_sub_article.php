<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_user_sub_article extends Model
{
    use HasFactory;

    protected $table = 'awb_user_sub_article';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
