<?php

namespace App\Models\Findtime;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ideation_slider extends Model
{
    use HasFactory;

    protected $table = 'ideation_slider';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
