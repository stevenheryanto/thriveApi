<?php

namespace App\Models\AWB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class awb_tmp_point_history extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'awb_tmp_point_history';
    protected $guarded = [];
}
