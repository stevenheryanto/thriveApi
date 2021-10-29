<?php

namespace App\Models\TTT;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_log';
    const CREATED_AT = 'date_created';
    const UPDATED_AT = 'date_modified';
}
