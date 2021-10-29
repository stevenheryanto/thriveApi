<?php

namespace App\Models\TTR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity_log extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'activity_log';
}
