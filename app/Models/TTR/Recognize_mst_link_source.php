<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recognize_mst_link_source extends Model
{
    use HasFactory;

    protected $table = 'recognize_mst_link_source';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
