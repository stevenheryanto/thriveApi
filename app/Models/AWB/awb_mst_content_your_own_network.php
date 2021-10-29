<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_mst_content_your_own_network extends Model
{
    use HasFactory;

    protected $table = 'awb_mst_content_your_own_network';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
